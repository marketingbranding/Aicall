<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'persona_version_id', 'key', 'title', 'information',
    'sensitivity', 'disclosure_difficulty', 'relevant_topics_json',
    'direct_question_effectiveness', 'trust_requirement',
    'disclosure_conditions_json', 'is_active',
])]
class PersonaHiddenInformation extends Model
{
    protected function casts(): array
    {
        return [
            'relevant_topics_json' => 'array',
            'disclosure_conditions_json' => 'array',
            'sensitivity' => 'integer',
            'disclosure_difficulty' => 'integer',
            'direct_question_effectiveness' => 'integer',
            'trust_requirement' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function personaVersion(): BelongsTo
    {
        return $this->belongsTo(PersonaVersion::class);
    }
}
