<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'persona_id', 'version_number', 'public_profile_text',
    'identity_json', 'housing_context_json', 'knowledge_beliefs_json',
    'personality_profile_json', 'human_behavior_traits_json',
    'communication_style_json', 'initial_dynamic_state_json',
    'state_sensitivity_json', 'salience_overrides_json',
    'created_by', 'created_at',
])]
class PersonaVersion extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'identity_json' => 'array',
            'housing_context_json' => 'array',
            'knowledge_beliefs_json' => 'array',
            'personality_profile_json' => 'array',
            'human_behavior_traits_json' => 'array',
            'communication_style_json' => 'array',
            'initial_dynamic_state_json' => 'array',
            'state_sensitivity_json' => 'array',
            'salience_overrides_json' => 'array',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function objections(): HasMany
    {
        return $this->hasMany(PersonaObjection::class, 'persona_version_id')->orderBy('id');
    }

    public function hiddenInformation(): HasMany
    {
        return $this->hasMany(PersonaHiddenInformation::class, 'persona_version_id')->orderBy('id');
    }

    public function replicateForPersona(Persona $newPersona, User $user): self
    {
        $replica = $this->replicate()->fill([
            'persona_id' => $newPersona->id,
            'version_number' => 1,
            'created_by' => $user->id,
        ]);
        $replica->created_at = now();
        $replica->save();

        foreach ($this->objections as $objection) {
            $objection->replicate()->fill([
                'persona_version_id' => $replica->id,
            ])->save();
        }

        foreach ($this->hiddenInformation as $info) {
            $info->replicate()->fill([
                'persona_version_id' => $replica->id,
            ])->save();
        }

        return $replica;
    }
}
