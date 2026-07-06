<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'scenario_version_id', 'persona_id', 'is_enabled', 'weight',
])]
class ScenarioPersona extends Model
{
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'weight' => 'integer',
        ];
    }

    public function scenarioVersion(): BelongsTo
    {
        return $this->belongsTo(ScenarioVersion::class);
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }
}
