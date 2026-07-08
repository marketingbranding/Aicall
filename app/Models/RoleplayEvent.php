<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoleplayEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'roleplay_session_id',
        'event_type',
        'severity',
        'topic',
        'related_objection_key',
        'hidden_information_key',
        'short_internal_reason',
        'source_turn_sequence',
        'fingerprint',
        'accepted',
        'rejection_reason',
        'previous_state_json',
        'new_state_json',
    ];

    protected function casts(): array
    {
        return [
            'accepted' => 'boolean',
            'previous_state_json' => 'array',
            'new_state_json' => 'array',
            'source_turn_sequence' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(RoleplaySession::class, 'roleplay_session_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(DirectorNote::class, 'roleplay_event_id');
    }
}
