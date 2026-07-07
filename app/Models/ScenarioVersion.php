<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'scenario_id', 'version_number',
    'description', 'sales_briefing', 'hidden_context', 'training_objective',
    'starting_phase', 'first_speaker', 'ai_opening_context',
    'initial_customer_intent',
    'target_behaviors_json', 'important_discovery_points_json',
    'mandatory_topics_json', 'prohibited_claims_json',
    'success_conditions_json', 'failure_conditions_json',
    'difficulty_level', 'difficulty_config_json',
    'max_duration_seconds', 'allow_ai_end_call',
    'allowed_persona_modes_json', 'created_by', 'created_at',
])]
class ScenarioVersion extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'target_behaviors_json' => 'array',
            'important_discovery_points_json' => 'array',
            'mandatory_topics_json' => 'array',
            'prohibited_claims_json' => 'array',
            'success_conditions_json' => 'array',
            'failure_conditions_json' => 'array',
            'difficulty_config_json' => 'array',
            'allowed_persona_modes_json' => 'array',
            'allow_ai_end_call' => 'boolean',
        ];
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedPersonas(): HasMany
    {
        return $this->hasMany(ScenarioPersona::class, 'scenario_version_id');
    }

    public function rubricOverrides(): HasMany
    {
        return $this->hasMany(ScenarioRubricOverride::class, 'scenario_version_id');
    }

    public function replicateForScenario(Scenario $newScenario, User $user): self
    {
        $replica = $this->replicate()->fill([
            'scenario_id' => $newScenario->id,
            'version_number' => 1,
            'created_by' => $user->id,
        ]);
        $replica->created_at = now();
        $replica->save();

        foreach ($this->assignedPersonas as $assignment) {
            $assignment->replicate()->fill([
                'scenario_version_id' => $replica->id,
            ])->save();
        }

        return $replica;
    }
}
