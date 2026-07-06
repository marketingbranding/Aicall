<?php

namespace App\Http\Controllers\Hq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hq\UpsertScenarioRequest;
use App\Models\Scenario;
use App\Models\ScenarioVersion;
use App\Models\Persona;
use App\Services\Scenarios\ScenarioBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScenarioController extends Controller
{
    public function __construct(
        private readonly ScenarioBuilderService $builderService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Scenario::class);

        $scenarios = Scenario::with('currentVersion', 'createdBy')
            ->orderBy('name')
            ->get();

        return view('hq.scenarios.index', compact('scenarios'));
    }

    public function create(): View
    {
        $this->authorize('create', Scenario::class);

        $personas = Persona::where('status', Persona::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('hq.scenarios.create', compact('personas'));
    }

    public function store(UpsertScenarioRequest $request): RedirectResponse
    {
        $scenario = Scenario::create([
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'status' => Scenario::STATUS_ACTIVE,
            'created_by' => $request->user()->id,
        ]);

        $version = ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'description' => $request->input('description'),
            'sales_briefing' => $request->input('sales_briefing'),
            'hidden_context' => $request->input('hidden_context'),
            'training_objective' => $request->input('training_objective'),
            'starting_phase' => $request->input('starting_phase'),
            'first_speaker' => $request->input('first_speaker', 'AI'),
            'ai_opening_context' => $request->input('ai_opening_context'),
            'initial_customer_intent' => $request->input('initial_customer_intent'),
            'target_behaviors_json' => $this->builderService->buildTargetBehaviors($request),
            'important_discovery_points_json' => $this->builderService->buildDiscoveryPoints($request),
            'mandatory_topics_json' => $this->builderService->buildMandatoryTopics($request),
            'prohibited_claims_json' => $this->builderService->buildProhibitedClaims($request),
            'success_conditions_json' => $this->builderService->buildSuccessConditions($request),
            'failure_conditions_json' => $this->builderService->buildFailureConditions($request),
            'difficulty_level' => $request->input('difficulty_level', 'NORMAL'),
            'difficulty_config_json' => $this->builderService->buildDifficultyConfig($request),
            'max_duration_seconds' => $request->input('max_duration_seconds'),
            'allow_ai_end_call' => $request->boolean('allow_ai_end_call'),
            'allowed_persona_modes_json' => $this->builderService->buildAllowedPersonaModes($request),
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        $this->builderService->syncAssignedPersonas($request, $version);

        $scenario->update(['current_version_id' => $version->id]);

        return redirect()->route('hq.scenarios.index')
            ->with('success', 'Skenario berhasil dibuat.');
    }

    public function edit(Scenario $scenario): View
    {
        $this->authorize('update', $scenario);

        $scenario->load('currentVersion.assignedPersonas');

        $personas = Persona::where('status', Persona::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('hq.scenarios.edit', compact('scenario', 'personas'));
    }

    public function update(UpsertScenarioRequest $request, Scenario $scenario): RedirectResponse
    {
        $scenario->update([
            'code' => $request->input('code'),
            'name' => $request->input('name'),
        ]);

        $currentVersion = $scenario->currentVersion;
        $nextVersion = $currentVersion ? $currentVersion->version_number + 1 : 1;

        $version = ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => $nextVersion,
            'description' => $request->input('description'),
            'sales_briefing' => $request->input('sales_briefing'),
            'hidden_context' => $request->input('hidden_context'),
            'training_objective' => $request->input('training_objective'),
            'starting_phase' => $request->input('starting_phase'),
            'first_speaker' => $request->input('first_speaker', 'AI'),
            'ai_opening_context' => $request->input('ai_opening_context'),
            'initial_customer_intent' => $request->input('initial_customer_intent'),
            'target_behaviors_json' => $this->builderService->buildTargetBehaviors($request),
            'important_discovery_points_json' => $this->builderService->buildDiscoveryPoints($request),
            'mandatory_topics_json' => $this->builderService->buildMandatoryTopics($request),
            'prohibited_claims_json' => $this->builderService->buildProhibitedClaims($request),
            'success_conditions_json' => $this->builderService->buildSuccessConditions($request),
            'failure_conditions_json' => $this->builderService->buildFailureConditions($request),
            'difficulty_level' => $request->input('difficulty_level', 'NORMAL'),
            'difficulty_config_json' => $this->builderService->buildDifficultyConfig($request),
            'max_duration_seconds' => $request->input('max_duration_seconds'),
            'allow_ai_end_call' => $request->boolean('allow_ai_end_call'),
            'allowed_persona_modes_json' => $this->builderService->buildAllowedPersonaModes($request),
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        $this->builderService->syncAssignedPersonas($request, $version);

        $scenario->update(['current_version_id' => $version->id]);

        return redirect()->route('hq.scenarios.index')
            ->with('success', 'Skenario berhasil diperbarui.');
    }

    public function archive(Request $request, Scenario $scenario): RedirectResponse
    {
        $this->authorize('archive', $scenario);

        $scenario->archive();

        return redirect()->route('hq.scenarios.index')
            ->with('success', 'Skenario berhasil diarsipkan.');
    }

    public function duplicate(Request $request, Scenario $scenario): RedirectResponse
    {
        $this->authorize('duplicate', $scenario);

        $clone = $scenario->duplicate($request->user());

        return redirect()->route('hq.scenarios.index')
            ->with('success', 'Skenario berhasil digandakan sebagai ' . $clone->name . '.');
    }
}
