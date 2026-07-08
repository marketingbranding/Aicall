<?php

namespace App\Http\Controllers;

use App\Enums\PersonaMode;
use App\Enums\RoleplaySessionStatus;
use App\Models\EvaluationRubric;
use App\Models\Persona;
use App\Models\RoleplaySession;
use App\Models\Scenario;
use App\Models\ScenarioVersion;
use App\Services\Director\DifficultyLevel;
use App\Services\Director\DifficultyModifier;
use App\Services\Director\DirectorState;
use App\Services\Personas\PersonaSalienceCompiler;
use App\Services\Personas\RoleplayInstructionCompiler;
use App\Services\Rubrics\RubricMerger;
use App\Services\Snapshots\SessionSnapshotService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrainingScenarioController extends Controller
{
    public function briefing(Request $request, Scenario $scenario): View
    {
        abort_if($scenario->isArchived(), 404);

        $version = $scenario->currentVersion;
        $idempotencyKey = Str::uuid()->toString();

        $availablePersonas = collect();
        if ($version) {
            $assigned = $version->assignedPersonas()
                ->where('is_enabled', true)
                ->whereHas('persona', fn ($q) => $q->where('status', Persona::STATUS_ACTIVE))
                ->with('persona.currentVersion')
                ->get();

            $availablePersonas = $assigned->pluck('persona')->filter();
        }

        return view('training.briefing', compact('scenario', 'version', 'availablePersonas', 'idempotencyKey'));
    }

    public function createSession(
        Request $request,
        Scenario $scenario,
        PersonaSalienceCompiler $salienceCompiler,
        RoleplayInstructionCompiler $instructionCompiler,
        RubricMerger $rubricMerger,
        SessionSnapshotService $snapshotService,
    ): RedirectResponse {
        abort_if($scenario->isArchived(), 404);

        $version = $scenario->currentVersion;
        abort_if(! $version, 404);

        $validated = $request->validate([
            'persona_mode' => ['required', 'string'],
            'persona_id' => ['nullable', 'integer'],
            'idempotency_key' => ['required', 'string', 'max:64'],
        ]);

        $mode = PersonaMode::tryFrom($validated['persona_mode']);
        if (! $mode) {
            throw ValidationException::withMessages([
                'persona_mode' => 'Mode persona tidak valid.',
            ]);
        }

        $allowedModes = $version->allowed_persona_modes_json ?? [];
        if (! in_array($mode->value, $allowedModes, true)) {
            throw ValidationException::withMessages([
                'persona_mode' => 'Mode persona tidak tersedia untuk skenario ini.',
            ]);
        }

        $fingerprint = $this->buildIdempotencyFingerprint(
            userId: $request->user()->id,
            scenario: $scenario,
            version: $version,
            mode: $mode,
            personaId: $validated['persona_id'] ?? null,
        );

        if ($existingSession = $this->findIdempotentSession($request, $validated['idempotency_key'], $fingerprint)) {
            return redirect()->route('training.sessions.prepare', $existingSession->public_id);
        }

        try {
            $session = DB::transaction(function () use (
                $request,
                $scenario,
                $version,
                $validated,
                $mode,
                $fingerprint,
                $salienceCompiler,
                $instructionCompiler,
                $rubricMerger,
                $snapshotService,
            ) {
                $persona = $this->resolvePersona($version, $mode, $validated['persona_id'] ?? null);
                $personaVersion = $persona->currentVersion;

                if (! $personaVersion) {
                    throw ValidationException::withMessages([
                        'persona_id' => 'Persona belum memiliki konfigurasi aktif.',
                    ]);
                }

                $difficultyLevel = $version->difficulty_level ?? DifficultyLevel::NORMAL->value;
                $isCustomDifficulty = $difficultyLevel === DifficultyLevel::CUSTOM->value;
                $difficultyModifier = $isCustomDifficulty
                    ? DifficultyModifier::fromCustomConfig($version->difficulty_config_json ?? [])
                    : DifficultyModifier::forLevel(DifficultyLevel::tryFrom($difficultyLevel) ?? DifficultyLevel::NORMAL);

                $salience = $salienceCompiler->compile($personaVersion);
                $actorInstructions = $instructionCompiler->compile($personaVersion, $salience, $version);

                $rubricResult = $rubricMerger->merge(
                    EvaluationRubric::query()
                        ->where('type', EvaluationRubric::TYPE_GLOBAL)
                        ->where('is_active', true)
                        ->with('items')
                        ->get(),
                    EvaluationRubric::query()
                        ->where('type', EvaluationRubric::TYPE_SCENARIO)
                        ->where('scenario_version_id', $version->id)
                        ->with('items')
                        ->first(),
                    $version->rubricOverrides()->get(),
                );

                $session = RoleplaySession::create([
                    'public_id' => RoleplaySession::generatePublicId(),
                    'correlation_id' => Str::uuid()->toString(),
                    'user_id' => $request->user()->id,
                    'branch_id' => $request->user()->branch_id,
                    'scenario_id' => $scenario->code,
                    'persona_id' => $persona->code,
                    'persona_mode' => $mode->value,
                    'difficulty_level' => $difficultyLevel,
                    'status' => RoleplaySessionStatus::CREATED->value,
                    'transcript_integrity' => null,
                    'evaluation_status' => null,
                    'director_version' => 1,
                    'idempotency_key' => $validated['idempotency_key'],
                    'idempotency_fingerprint' => $fingerprint,
                ]);

                $snapshot = $snapshotService->createSnapshot(
                    personaVersion: $personaVersion,
                    scenarioVersion: $version,
                    difficultyModifier: $difficultyModifier,
                    difficultyLevel: $difficultyLevel,
                    isCustomDifficulty: $isCustomDifficulty,
                    salienceResult: $salience,
                    rubricResult: $rubricResult,
                    initialState: DirectorState::default(),
                    actorInstructions: $actorInstructions,
                );
                $snapshot->roleplay_session_id = $session->id;
                $snapshot->created_at = now();
                $snapshot->save();

                return $session;
            });
        } catch (QueryException $exception) {
            $session = $this->findIdempotentSession($request, $validated['idempotency_key'], $fingerprint);
            if (! $session) {
                throw $exception;
            }
        }

        return redirect()->route('training.sessions.prepare', $session->public_id);
    }

    public function prepare(Request $request, string $publicId): View
    {
        $session = RoleplaySession::query()
            ->where('public_id', $publicId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $snapshot = $session->snapshot;
        $scenarioName = $snapshot?->scenario_snapshot_json['name'] ?? 'Latihan';
        $maxDurationSeconds = $snapshot?->scenario_snapshot_json['max_duration_seconds'] ?? 900;

        return view('training.prepare', compact('session', 'scenarioName', 'maxDurationSeconds'));
    }

    private function resolvePersona(ScenarioVersion $version, PersonaMode $mode, ?int $personaId): Persona
    {
        $query = $version->assignedPersonas()
            ->where('is_enabled', true)
            ->whereHas('persona', fn ($q) => $q->where('status', Persona::STATUS_ACTIVE))
            ->with(['persona.currentVersion.objections', 'persona.currentVersion.hiddenInformation']);

        if ($mode === PersonaMode::CHOOSE_PERSONA) {
            if (! $personaId) {
                throw ValidationException::withMessages([
                    'persona_id' => 'Pilih persona terlebih dahulu.',
                ]);
            }

            $assignment = (clone $query)->where('persona_id', $personaId)->first();

            if (! $assignment?->persona) {
                throw ValidationException::withMessages([
                    'persona_id' => 'Persona tidak tersedia untuk skenario ini.',
                ]);
            }

            return $assignment->persona;
        }

        $assignment = $query->inRandomOrder()->first();
        if (! $assignment?->persona) {
            throw ValidationException::withMessages([
                'persona_mode' => 'Belum ada persona aktif untuk skenario ini.',
            ]);
        }

        return $assignment->persona;
    }

    private function findIdempotentSession(Request $request, string $key, string $fingerprint): ?RoleplaySession
    {
        $session = RoleplaySession::query()
            ->where('user_id', $request->user()->id)
            ->where('idempotency_key', $key)
            ->first();

        if (! $session) {
            return null;
        }

        if (! hash_equals((string) $session->idempotency_fingerprint, $fingerprint)) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'Token pengiriman ini sudah digunakan untuk permintaan lain.',
            ]);
        }

        return $session;
    }

    private function buildIdempotencyFingerprint(
        int $userId,
        Scenario $scenario,
        ScenarioVersion $version,
        PersonaMode $mode,
        ?int $personaId,
    ): string {
        return hash('sha256', json_encode([
            'user_id' => $userId,
            'scenario_id' => $scenario->id,
            'scenario_version_id' => $version->id,
            'persona_mode' => $mode->value,
            'persona_id' => $mode === PersonaMode::CHOOSE_PERSONA ? $personaId : null,
        ], JSON_THROW_ON_ERROR));
    }
}
