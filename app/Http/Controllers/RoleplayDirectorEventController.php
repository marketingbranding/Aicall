<?php

namespace App\Http\Controllers;

use App\Enums\RoleplaySessionStatus;
use App\Models\DirectorNote;
use App\Models\RoleplayEvent;
use App\Models\RoleplaySession;
use App\Services\Director\DirectorEngineFactory;
use App\Services\Director\DirectorNoteCooldown;
use App\Services\Director\DirectorNotePlanner;
use App\Services\Director\RoleplayEvent as DirectorEventDto;
use App\Services\Director\RoleplayEventType;
use App\Services\Director\StateToBehaviorTranslator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleplayDirectorEventController extends Controller
{
    public function __construct(
        private readonly DirectorEngineFactory $factory,
        private readonly StateToBehaviorTranslator $translator,
        private readonly DirectorNotePlanner $planner,
    ) {}

    public function store(Request $request, string $publicId): JsonResponse
    {
        $session = RoleplaySession::query()
            ->where('public_id', $publicId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!$session->canReceiveEvents()) {
            return response()->json([
                'message' => 'Sesi tidak aktif atau sudah berakhir.',
                'error' => 'invalid_session_status',
            ], 409);
        }

        $validated = $request->validate([
            'event_type' => ['required', 'string', function (string $attr, string $value, \Closure $fail): void {
                if (RoleplayEventType::tryFrom($value) === null) {
                    $fail("{$value} bukan RoleplayEventType yang valid.");
                }
            }],
            'severity' => ['sometimes', 'string', 'in:LOW,MODERATE,HIGH,CRITICAL'],
            'topic' => ['sometimes', 'string', 'max:255'],
            'related_objection_key' => ['sometimes', 'string', 'max:100', 'nullable'],
            'hidden_information_key' => ['sometimes', 'string', 'max:100', 'nullable'],
            'short_internal_reason' => ['sometimes', 'string', 'max:500', 'nullable'],
            'source_turn_sequence' => ['sometimes', 'integer', 'min:0', 'nullable'],
        ]);

        $eventDto = new DirectorEventDto(
            type: RoleplayEventType::from($validated['event_type']),
            topic: $validated['topic'] ?? '',
            severity: $validated['severity'] ?? 'MODERATE',
            relatedObjectionKey: $validated['related_objection_key'] ?? null,
            shortInternalReason: $validated['short_internal_reason'] ?? '',
        );

        $fingerprint = $eventDto->fingerprint();

        $existing = RoleplayEvent::where('roleplay_session_id', $session->id)
            ->where('fingerprint', $fingerprint)
            ->first();

        if ($existing !== null) {
            $notes = DirectorNote::where('roleplay_event_id', $existing->id)->get();

            return response()->json([
                'accepted' => $existing->accepted,
                'rejection_reason' => $existing->rejection_reason,
                'state' => $existing->new_state_json,
                'notes' => $notes->toArray(),
                'event' => [
                    'id' => $existing->id,
                    'event_type' => $existing->event_type,
                    'fingerprint' => $existing->fingerprint,
                ],
            ]);
        }

        [$engine, $state, $objMachine, $hiMachine, $bndMachine, $phaseManager] = $this->factory->buildForSession($session);

        $result = $engine->applyEvent($eventDto, $state);

        $eventRecord = RoleplayEvent::create([
            'roleplay_session_id' => $session->id,
            'event_type' => $validated['event_type'],
            'severity' => $validated['severity'] ?? 'MODERATE',
            'topic' => $validated['topic'] ?? '',
            'related_objection_key' => $validated['related_objection_key'] ?? null,
            'hidden_information_key' => $validated['hidden_information_key'] ?? null,
            'short_internal_reason' => $validated['short_internal_reason'] ?? null,
            'source_turn_sequence' => $validated['source_turn_sequence'] ?? null,
            'fingerprint' => $fingerprint,
            'accepted' => $result->accepted,
            'rejection_reason' => $result->rejectionReason,
            'previous_state_json' => $state->toArray(),
            'new_state_json' => $result->state->toArray(),
        ]);

        $savedNotes = [];
        if ($result->accepted) {
            $translation = $this->translator->translate($result->state);

            $rawNotes = $this->planner->planNotes(
                previousState: $state,
                newState: $result->state,
                translation: $translation,
                objectionTransitions: $result->objectionTransitions,
                hiddenInfoTransitions: $result->hiddenInfoTransitions,
                boundaryTransitions: $result->boundaryTransitions,
                phaseTransitions: $result->phaseTransitions,
            );

            $cooldown = new DirectorNoteCooldown();
            $eventCount = ($session->directorStateSnapshot?->event_count ?? 0) + 1;
            for ($i = 0; $i < $eventCount; $i++) {
                $cooldown->nextTurn();
            }

            foreach ($rawNotes as $note) {
                if ($cooldown->isAllowed($note)) {
                    $cooldown->record($note);
                    $savedNotes[] = DirectorNote::create([
                        'roleplay_session_id' => $session->id,
                        'roleplay_event_id' => $eventRecord->id,
                        'text' => $note->text,
                        'category' => $note->category,
                        'priority' => $note->priority,
                        'source_turn' => $eventCount,
                    ]);
                }
            }
        }

        $this->factory->saveState(
            $session, $result->state,
            $objMachine, $hiMachine, $bndMachine, $phaseManager,
        );

        return response()->json([
            'accepted' => $result->accepted,
            'rejection_reason' => $result->rejectionReason,
            'state' => $result->state->toArray(),
            'applied_transition' => $result->appliedTransition->toArray(),
            'notes' => array_map(fn(DirectorNote $n) => [
                'text' => $n->text,
                'category' => $n->category,
                'priority' => $n->priority,
            ], $savedNotes),
            'event' => [
                'id' => $eventRecord->id,
                'event_type' => $eventRecord->event_type,
                'fingerprint' => $eventRecord->fingerprint,
            ],
        ]);
    }
}
