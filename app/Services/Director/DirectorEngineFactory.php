<?php

namespace App\Services\Director;

use App\Models\DirectorStateSnapshot;
use App\Models\RoleplaySession;
use App\Models\RoleplaySessionSnapshot;

readonly class DirectorEngineFactory
{
    public function buildForSession(RoleplaySession $session): array
    {
        $snapshot = $session->snapshot;
        $record = $session->directorStateSnapshot;

        $objectionMachine = new ObjectionStateMachine();
        $hiddenInfoMachine = new HiddenInfoStateMachine();
        $boundaryMachine = new BoundaryStateMachine();
        $diminishing = new DiminishingReturnCalculator();

        if ($record !== null) {
            $ms = $record->machine_states_json;

            if ($snapshot !== null) {
                $this->registerObjectionsFromSnapshot($objectionMachine, $snapshot->persona_snapshot_json);
                $this->registerHiddenInfoFromSnapshot($hiddenInfoMachine, $snapshot->persona_snapshot_json);
            }

            $this->restoreObjectionStates($objectionMachine, $ms['objections']['states'] ?? []);
            $this->restoreHiddenInfoStates($hiddenInfoMachine, $ms['hidden_info']['states'] ?? []);

            $boundaryState = BoundaryState::tryFrom($ms['boundary']['current_state'] ?? '') ?? BoundaryState::NOT_TESTED;
            $boundaryMachine->restoreState($boundaryState);

            $phase = ConversationPhase::tryFrom($ms['phase']['current_phase'] ?? '') ?? ConversationPhase::OPENING;
            $phaseManager = new ConversationPhaseManager($phase);
        } else {
            $phaseManager = new ConversationPhaseManager($this->getInitialPhase($snapshot));

            if ($snapshot !== null) {
                $this->registerObjectionsFromSnapshot($objectionMachine, $snapshot->persona_snapshot_json);
                $this->registerHiddenInfoFromSnapshot($hiddenInfoMachine, $snapshot->persona_snapshot_json);

                $bc = $snapshot->director_snapshot_json['boundary_config'] ?? [];
                if (!empty($bc)) {
                    if (isset($bc['respect_for_boundaries'])) {
                        $boundaryMachine->setRespectForBoundaries($bc['respect_for_boundaries']);
                    }
                    if (isset($bc['persistence_after_redirection'])) {
                        $boundaryMachine->setPersistenceAfterRedirection($bc['persistence_after_redirection']);
                    }
                }
            }
        }

        $difficultyModifier = $this->buildDifficultyModifier($session, $snapshot);

        $engine = new RoleplayDirectorEngine(
            objectionStateMachine: $objectionMachine,
            hiddenInfoStateMachine: $hiddenInfoMachine,
            boundaryStateMachine: $boundaryMachine,
            phaseManager: $phaseManager,
            diminishingCalculator: $diminishing,
        );
        $engine->setDifficultyModifier($difficultyModifier);

        $state = $this->buildDirectorState($record, $snapshot);

        return [$engine, $state, $objectionMachine, $hiddenInfoMachine, $boundaryMachine, $phaseManager];
    }

    public function saveState(
        RoleplaySession $session,
        DirectorState $state,
        ObjectionStateMachine $objectionMachine,
        HiddenInfoStateMachine $hiddenInfoMachine,
        BoundaryStateMachine $boundaryMachine,
        ConversationPhaseManager $phaseManager,
    ): DirectorStateSnapshot {
        $ms = [
            'objections' => [
                'states' => $objectionMachine->getStateMap(),
            ],
            'hidden_info' => [
                'states' => $hiddenInfoMachine->getStateMap(),
            ],
            'boundary' => $boundaryMachine->toArray(),
            'phase' => [
                'current_phase' => $phaseManager->getCurrentPhase()->value,
            ],
        ];

        $record = DirectorStateSnapshot::updateOrCreate(
            ['roleplay_session_id' => $session->id],
            [
                'state_json' => $state->toArray(),
                'machine_states_json' => $ms,
            ],
        );

        $record->increment('event_count');

        return $record;
    }

    public function buildDirectorState(?DirectorStateSnapshot $record, ?RoleplaySessionSnapshot $snapshot): DirectorState
    {
        if ($record !== null) {
            $s = $record->state_json;
            return new DirectorState(
                trust: $s['trust'] ?? 50,
                interest: $s['interest'] ?? 50,
                confusion: $s['confusion'] ?? 10,
                anxiety: $s['anxiety'] ?? 30,
                irritation: $s['irritation'] ?? 10,
                pressurePerception: $s['pressure_perception'] ?? 10,
                engagement: $s['engagement'] ?? 50,
            );
        }

        if ($snapshot !== null) {
            $initial = $snapshot->director_snapshot_json['initial_state'] ?? [];
            return new DirectorState(
                trust: $initial['trust'] ?? 50,
                interest: $initial['interest'] ?? 50,
                confusion: $initial['confusion'] ?? 10,
                anxiety: $initial['anxiety'] ?? 30,
                irritation: $initial['irritation'] ?? 10,
                pressurePerception: $initial['pressure_perception'] ?? 10,
                engagement: $initial['engagement'] ?? 50,
            );
        }

        return DirectorState::default();
    }

    private function buildDifficultyModifier(RoleplaySession $session, ?RoleplaySessionSnapshot $snapshot): DifficultyModifier
    {
        if ($snapshot !== null) {
            $ds = $snapshot->difficulty_snapshot_json;
            $level = DifficultyLevel::tryFrom($ds['level'] ?? '') ?? DifficultyLevel::NORMAL;

            if ($ds['is_custom'] ?? false) {
                return DifficultyModifier::fromCustomConfig($ds);
            }

            return DifficultyModifier::forLevel($level);
        }

        return DifficultyModifier::forLevel(
            DifficultyLevel::tryFrom($session->difficulty_level) ?? DifficultyLevel::NORMAL,
        );
    }

    private function getInitialPhase(?RoleplaySessionSnapshot $snapshot): ConversationPhase
    {
        if ($snapshot === null) {
            return ConversationPhase::OPENING;
        }

        return ConversationPhase::tryFrom(
            $snapshot->director_snapshot_json['initial_phase'] ?? 'OPENING',
        ) ?? ConversationPhase::OPENING;
    }

    private function registerObjectionsFromSnapshot(ObjectionStateMachine $machine, ?array $persona): void
    {
        if ($persona === null) {
            return;
        }

        foreach ($persona['objections'] ?? [] as $obj) {
            $machine->register(
                key: $obj['key'] ?? 'unknown',
                visibility: $obj['visibility'] ?? 'VISIBLE',
                persistence: $obj['persistence'] ?? 50,
                title: $obj['title'] ?? '',
            );
        }
    }

    private function registerHiddenInfoFromSnapshot(HiddenInfoStateMachine $machine, ?array $persona): void
    {
        if ($persona === null) {
            return;
        }

        foreach ($persona['hidden_information'] ?? [] as $hi) {
            $machine->register(
                key: $hi['key'] ?? 'unknown',
                title: $hi['title'] ?? '',
                sensitivity: $hi['sensitivity'] ?? 50,
                disclosureDifficulty: $hi['disclosure_difficulty'] ?? 50,
                relevantTopics: $hi['relevant_topics'] ?? [],
                directQuestionEffectiveness: $hi['direct_question_effectiveness'] ?? 50,
                trustRequirement: $hi['trust_requirement'] ?? 50,
            );
        }
    }

    private function restoreObjectionStates(ObjectionStateMachine $machine, array $stateMap): void
    {
        foreach ($stateMap as $key => $stateValue) {
            if ($machine->has($key)) {
                $machine->restoreState($key, ObjectionState::from($stateValue));
            }
        }
    }

    private function restoreHiddenInfoStates(HiddenInfoStateMachine $machine, array $stateMap): void
    {
        foreach ($stateMap as $key => $stateValue) {
            if ($machine->has($key)) {
                $machine->restoreState($key, HiddenInfoState::from($stateValue));
            }
        }
    }
}
