<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'persona_version_id', 'key', 'title', 'context', 'visibility',
    'severity', 'emotional_importance', 'trigger_conditions_json',
    'disclosure_conditions_json', 'resolution_conditions_json',
    'persistence', 'is_resolvable', 'is_active',
])]
class PersonaObjection extends Model
{
    protected function casts(): array
    {
        return [
            'trigger_conditions_json' => 'array',
            'disclosure_conditions_json' => 'array',
            'resolution_conditions_json' => 'array',
            'is_resolvable' => 'boolean',
            'is_active' => 'boolean',
            'severity' => 'integer',
            'emotional_importance' => 'integer',
            'persistence' => 'integer',
        ];
    }

    public function personaVersion(): BelongsTo
    {
        return $this->belongsTo(PersonaVersion::class);
    }
}
