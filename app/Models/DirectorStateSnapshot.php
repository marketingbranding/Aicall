<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectorStateSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'roleplay_session_id',
        'state_json',
        'machine_states_json',
        'event_count',
    ];

    protected function casts(): array
    {
        return [
            'state_json' => 'array',
            'machine_states_json' => 'array',
            'event_count' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(RoleplaySession::class, 'roleplay_session_id');
    }
}
