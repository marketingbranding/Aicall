<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class RoleplaySessionSnapshot extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'roleplay_session_id',
        'persona_version_id',
        'scenario_version_id',
        'persona_snapshot_json',
        'scenario_snapshot_json',
        'difficulty_snapshot_json',
        'salience_snapshot_json',
        'rubric_snapshot_json',
        'director_snapshot_json',
        'actor_instruction_hash',
        'actor_instructions',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'persona_snapshot_json' => 'array',
            'scenario_snapshot_json' => 'array',
            'difficulty_snapshot_json' => 'array',
            'salience_snapshot_json' => 'array',
            'rubric_snapshot_json' => 'array',
            'director_snapshot_json' => 'array',
            'actor_instructions' => 'encrypted',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Roleplay session snapshots are immutable after creation.');
        });
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(RoleplaySession::class, 'roleplay_session_id');
    }

    public function personaVersion(): BelongsTo
    {
        return $this->belongsTo(PersonaVersion::class, 'persona_version_id');
    }

    public function scenarioVersion(): BelongsTo
    {
        return $this->belongsTo(ScenarioVersion::class, 'scenario_version_id');
    }
}
