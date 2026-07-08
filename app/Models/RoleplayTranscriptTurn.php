<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleplayTranscriptTurn extends Model
{
    use HasFactory;

    protected $fillable = [
        'roleplay_session_id',
        'sequence',
        'speaker',
        'text',
        'status',
        'started_at',
        'ended_at',
        'source_metadata',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'source_metadata' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(RoleplaySession::class, 'roleplay_session_id');
    }
}
