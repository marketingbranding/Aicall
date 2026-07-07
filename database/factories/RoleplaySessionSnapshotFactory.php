<?php

namespace Database\Factories;

use App\Models\RoleplaySession;
use App\Models\RoleplaySessionSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleplaySessionSnapshotFactory extends Factory
{
    protected $model = RoleplaySessionSnapshot::class;

    public function definition(): array
    {
        $instructionText = '=== AKTOR PERSONA ===' . "\nTest persona instructions.";

        return [
            'roleplay_session_id' => RoleplaySession::factory(),
            'persona_version_id' => null,
            'scenario_version_id' => null,
            'persona_snapshot_json' => [
                'persona_key' => 'test-persona',
                'name' => 'Test Persona',
                'version_number' => 1,
                'identity' => ['name' => 'Test', 'age' => 30],
                'housing_context' => [],
                'knowledge_beliefs' => [],
                'misconceptions' => [],
                'personality_profile' => [],
                'human_behavior_traits' => [],
                'communication_style' => [],
                'initial_dynamic_state' => [],
                'state_sensitivity' => [],
                'objections' => [],
                'hidden_information' => [],
                'salience_overrides' => [],
            ],
            'scenario_snapshot_json' => [
                'scenario_key' => 'test-scenario',
                'name' => 'Test Scenario',
                'version_number' => 1,
                'description' => 'Test description',
                'sales_briefing' => null,
                'training_objective' => null,
                'hidden_context' => null,
                'starting_phase' => 'OPENING',
                'first_speaker' => 'SALES',
                'ai_opening_context' => null,
                'initial_customer_intent' => null,
                'target_behaviors' => [],
                'important_discovery_points' => [],
                'mandatory_topics' => [],
                'prohibited_claims' => [],
                'success_conditions' => [],
                'failure_conditions' => [],
                'difficulty_level' => 'NORMAL',
                'difficulty_config' => null,
                'max_duration_seconds' => 900,
                'allow_ai_end_call' => false,
                'allowed_persona_modes' => [],
            ],
            'difficulty_snapshot_json' => [
                'level' => 'NORMAL',
                'is_custom' => false,
                'trust_gain_multiplier' => 1.0,
                'trust_loss_multiplier' => 1.0,
                'disclosure_resistance' => 50,
                'objection_persistence' => 50,
                'irritation_sensitivity' => 50,
                'weak_explanation_tolerance' => 50,
                'closing_resistance' => 50,
                'boundary_persistence' => 50,
            ],
            'salience_snapshot_json' => [
                'primary' => [],
                'secondary' => [],
                'background' => [],
            ],
            'rubric_snapshot_json' => [
                'items' => [],
            ],
            'director_snapshot_json' => [
                'initial_state' => [
                    'trust' => 50, 'interest' => 50, 'confusion' => 10,
                    'anxiety' => 30, 'irritation' => 10,
                    'pressure_perception' => 10, 'engagement' => 50,
                ],
                'difficulty_values' => [],
                'objection_config' => [],
                'hidden_info_config' => [],
                'boundary_config' => [],
                'initial_phase' => 'OPENING',
            ],
            'actor_instruction_hash' => hash('sha256', $instructionText),
            'actor_instructions' => $instructionText,
            'created_at' => now(),
        ];
    }
}
