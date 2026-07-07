<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'scenario_version_id', 'global_rubric_item_key',
    'weight_override', 'is_enabled_override',
])]
class ScenarioRubricOverride extends Model
{
    protected function casts(): array
    {
        return [
            'weight_override' => 'integer',
            'is_enabled_override' => 'boolean',
        ];
    }

    public function scenarioVersion(): BelongsTo
    {
        return $this->belongsTo(ScenarioVersion::class, 'scenario_version_id');
    }
}
