<?php

namespace App\Models;

use Database\Factories\EvaluationRubricFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'type', 'scenario_version_id', 'version_number', 'is_active', 'created_by'])]
class EvaluationRubric extends Model
{
    /** @use HasFactory<EvaluationRubricFactory> */
    use HasFactory;

    public const TYPE_GLOBAL = 'GLOBAL';

    public const TYPE_SCENARIO = 'SCENARIO';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'version_number' => 'integer',
        ];
    }

    public function isGlobal(): bool
    {
        return $this->type === self::TYPE_GLOBAL;
    }

    public function isScenario(): bool
    {
        return $this->type === self::TYPE_SCENARIO;
    }

    public function items(): HasMany
    {
        return $this->hasMany(EvaluationRubricItem::class, 'evaluation_rubric_id')->orderBy('id');
    }

    public function scenarioVersion(): BelongsTo
    {
        return $this->belongsTo(ScenarioVersion::class, 'scenario_version_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
