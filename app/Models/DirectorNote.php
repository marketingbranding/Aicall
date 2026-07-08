<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectorNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'roleplay_session_id',
        'roleplay_event_id',
        'text',
        'category',
        'priority',
        'source_turn',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'source_turn' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(RoleplaySession::class, 'roleplay_session_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(RoleplayEvent::class, 'roleplay_event_id');
    }
}
