<?php

namespace App\Models;

use App\Enums\EndingType;
use App\Enums\PersonaMode;
use App\Enums\RoleplaySessionStatus;
use App\Enums\EvaluationStatus;
use App\Enums\TranscriptIntegrity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class RoleplaySession extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'correlation_id',
        'user_id',
        'branch_id',
        'scenario_id',
        'persona_id',
        'persona_mode',
        'difficulty_level',
        'status',
        'started_at',
        'ended_at',
        'duration_seconds',
        'ending_type',
        'ending_reason',
        'transcript_integrity',
        'evaluation_status',
        'director_version',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
            'director_version' => 'integer',
        ];
    }

    public static function generatePublicId(): string
    {
        return Str::lower(Str::random(12));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function snapshot(): HasOne
    {
        return $this->hasOne(RoleplaySessionSnapshot::class, 'roleplay_session_id');
    }

    public function scopeForUser($query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    public function scopeActive($query): void
    {
        $query->whereIn('status', [
            RoleplaySessionStatus::ACTIVE->value,
            RoleplaySessionStatus::READY->value,
        ]);
    }

    public function scopeByBranch($query, int $branchId): void
    {
        $query->where('branch_id', $branchId);
    }

    public function scopeWhereStatus($query, RoleplaySessionStatus $status): void
    {
        $query->where('status', $status->value);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            RoleplaySessionStatus::ACTIVE->value,
            RoleplaySessionStatus::READY->value,
        ], true);
    }

    public function isEnding(): bool
    {
        return in_array($this->status, [
            RoleplaySessionStatus::ENDING->value,
            RoleplaySessionStatus::TRANSCRIPT_FINALIZING->value,
            RoleplaySessionStatus::EVALUATING->value,
        ], true);
    }

    public function canEnd(): bool
    {
        return !in_array($this->status, [
            RoleplaySessionStatus::COMPLETED->value,
            RoleplaySessionStatus::FAILED->value,
        ], true);
    }

    public function canReceiveEvents(): bool
    {
        return in_array($this->status, [
            RoleplaySessionStatus::ACTIVE->value,
            RoleplaySessionStatus::READY->value,
        ], true);
    }

    public function markEnded(string $endingType, ?string $reason = null): void
    {
        $this->update([
            'status' => RoleplaySessionStatus::ENDING->value,
            'ended_at' => now(),
            'ending_type' => $endingType,
            'ending_reason' => $reason,
        ]);
    }
}
