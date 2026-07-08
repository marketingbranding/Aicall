<?php

namespace App\Services\Snapshots;

use App\Models\PersonaVersion;
use App\Models\ScenarioVersion;
use App\Models\RoleplaySessionSnapshot;
use App\Services\Director\DifficultyModifier;
use App\Services\Director\DirectorState;
use App\Services\Personas\RoleplayInstruction;
use App\Services\Personas\SalienceResult;
use App\Services\Rubrics\RubricMergedResult;

class SessionSnapshotService
{
    public function createSnapshot(
        PersonaVersion $personaVersion,
        ScenarioVersion $scenarioVersion,
        DifficultyModifier $difficultyModifier,
        string $difficultyLevel,
        bool $isCustomDifficulty,
        SalienceResult $salienceResult,
        RubricMergedResult $rubricResult,
        DirectorState $initialState,
        RoleplayInstruction $actorInstructions,
    ): RoleplaySessionSnapshot {
        $personaSnapshot = $this->buildPersonaSnapshot($personaVersion);
        $scenarioSnapshot = $this->buildScenarioSnapshot($scenarioVersion);
        $salienceSnapshot = new SalienceSnapshot(
            primary: $salienceResult->primary,
            secondary: $salienceResult->secondary,
            background: $salienceResult->background,
        );

        $difficultySnapshot = new DifficultySnapshot(
            level: $difficultyLevel,
            isCustom: $isCustomDifficulty,
            trustGainMultiplier: $difficultyModifier->trustGainMultiplier,
            trustLossMultiplier: $difficultyModifier->trustLossMultiplier,
            disclosureResistance: $difficultyModifier->disclosureResistance,
            objectionPersistence: $difficultyModifier->objectionPersistence,
            irritationSensitivity: $difficultyModifier->irritationSensitivity,
            weakExplanationTolerance: $difficultyModifier->weakExplanationTolerance,
            closingResistance: $difficultyModifier->closingResistance,
            boundaryPersistence: $difficultyModifier->boundaryPersistence,
        );

        $rubricSnapshot = new RubricSnapshot(items: $rubricResult->toArray());

        $directorSnapshot = new DirectorSnapshot(
            initialState: $initialState->toArray(),
            difficultyValues: $difficultyModifier->toArray(),
            objectionConfig: [],
            hiddenInfoConfig: [],
            boundaryConfig: [],
            initialPhase: $scenarioVersion->starting_phase ?? 'OPENING',
        );

        $instructionText = $actorInstructions->toText();

        return new RoleplaySessionSnapshot([
            'persona_version_id' => $personaVersion->id,
            'scenario_version_id' => $scenarioVersion->id,
            'persona_snapshot_json' => $personaSnapshot->toArray(),
            'scenario_snapshot_json' => $scenarioSnapshot->toArray(),
            'difficulty_snapshot_json' => $difficultySnapshot->toArray(),
            'salience_snapshot_json' => $salienceSnapshot->toArray(),
            'rubric_snapshot_json' => $rubricSnapshot->toArray(),
            'director_snapshot_json' => $directorSnapshot->toArray(),
            'actor_instruction_hash' => hash('sha256', $instructionText),
            'actor_instructions' => $instructionText,
        ]);
    }

    private function buildPersonaSnapshot(PersonaVersion $version): PersonaSnapshot
    {
        $knowledgeBeliefs = $version->knowledge_beliefs_json ?? [];
        $misconceptions = array_values(
            array_filter($knowledgeBeliefs, fn($kb) => ($kb['reliability'] ?? '') === 'MISUNDERSTOOD'),
        );

        $objections = $version->objections->map(fn($o) => [
            'key' => $o->key,
            'title' => $o->title,
            'context' => $o->context,
            'visibility' => $o->visibility,
            'severity' => $o->severity,
            'emotional_importance' => $o->emotional_importance,
            'persistence' => $o->persistence,
            'is_resolvable' => $o->is_resolvable,
            'trigger_conditions' => $o->trigger_conditions,
            'disclosure_conditions' => $o->disclosure_conditions,
            'resolution_conditions' => $o->resolution_conditions,
        ])->toArray();

        $hiddenInfo = $version->hiddenInformation->map(fn($h) => [
            'key' => $h->key,
            'information' => $h->information,
            'sensitivity' => $h->sensitivity,
            'disclosure_difficulty' => $h->disclosure_difficulty,
            'trust_requirement' => $h->trust_requirement,
            'direct_question_effectiveness' => $h->direct_question_effectiveness,
            'relevant_topics' => $h->relevant_topics,
            'disclosure_conditions' => $h->disclosure_conditions,
        ])->toArray();

        return new PersonaSnapshot(
            personaKey: $version->persona->code,
            name: $version->persona->name,
            versionNumber: $version->version_number,
            identity: $version->identity_json ?? [],
            housingContext: $version->housing_context_json ?? [],
            knowledgeBeliefs: $knowledgeBeliefs,
            misconceptions: $misconceptions,
            personalityProfile: $version->personality_profile_json ?? [],
            humanBehaviorTraits: $version->human_behavior_traits_json ?? [],
            communicationStyle: $version->communication_style_json ?? [],
            initialDynamicState: $version->initial_dynamic_state_json ?? [],
            stateSensitivity: $version->state_sensitivity_json ?? [],
            objections: $objections,
            hiddenInformation: $hiddenInfo,
            salienceOverrides: $version->salience_overrides_json ?? [],
        );
    }

    private function buildScenarioSnapshot(ScenarioVersion $version): ScenarioSnapshot
    {
        return new ScenarioSnapshot(
            scenarioKey: $version->scenario->code,
            name: $version->scenario->name,
            versionNumber: $version->version_number,
            description: $version->description ?? '',
            salesBriefing: $version->sales_briefing ?? null,
            trainingObjective: $version->training_objective ?? null,
            hiddenContext: $version->hidden_context ?? null,
            startingPhase: $version->starting_phase ?? 'OPENING',
            firstSpeaker: $version->first_speaker ?? 'USER',
            aiOpeningContext: $version->ai_opening_context ?? null,
            initialCustomerIntent: $version->initial_customer_intent ?? null,
            targetBehaviors: $version->target_behaviors_json ?? [],
            importantDiscoveryPoints: $version->important_discovery_points_json ?? [],
            mandatoryTopics: $version->mandatory_topics_json ?? [],
            prohibitedClaims: $version->prohibited_claims_json ?? [],
            successConditions: $version->success_conditions_json ?? [],
            failureConditions: $version->failure_conditions_json ?? [],
            difficultyLevel: $version->difficulty_level ?? 'NORMAL',
            difficultyConfig: $version->difficulty_config_json ?? null,
            maxDurationSeconds: $version->max_duration_seconds ?? 900,
            allowAiEndCall: $version->allow_ai_end_call ?? false,
            allowedPersonaModes: $version->allowed_persona_modes_json ?? [],
        );
    }
}
