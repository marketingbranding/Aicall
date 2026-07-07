<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'evaluation_rubric_id', 'key', 'title', 'description',
    'weight', 'is_enabled', 'evaluation_guidance',
])]
class EvaluationRubricItem extends Model
{
    protected function casts(): array
    {
        return [
            'weight' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(EvaluationRubric::class, 'evaluation_rubric_id');
    }
}
