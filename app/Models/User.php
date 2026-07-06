<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['branch_id', 'name', 'email', 'password', 'role', 'status', 'approved_at', 'approved_by'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_SUSPENDED = 'SUSPENDED';

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isSales(): bool
    {
        return $this->role === UserRole::Sales;
    }

    public function canAccessHq(): bool
    {
        return $this->role->canAccessHq();
    }

    public function canManageBranches(): bool
    {
        return $this->role->canManageBranches();
    }

    public function canManageUsers(): bool
    {
        return $this->role->canManageUsers();
    }

    public function canApproveUsers(): bool
    {
        return $this->role->canApproveUsers();
    }

    public function canManagePersonas(): bool
    {
        return $this->role->canManagePersonas();
    }

    public function canManageScenarios(): bool
    {
        return $this->role->canManageScenarios();
    }

    public function canConfigureAiProviders(): bool
    {
        return $this->role->canConfigureAiProviders();
    }

    public function canViewAllTrainingSessions(): bool
    {
        return $this->role->canViewAllTrainingSessions();
    }

    public function approve(Branch $branch, User $approvedBy): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'branch_id' => $branch->id,
            'approved_at' => now(),
            'approved_by' => $approvedBy->id,
        ]);
    }

    public function suspend(): void
    {
        $this->update(['status' => self::STATUS_SUSPENDED]);
    }

    public function reactivate(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }
}
