<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageUsers();
    }

    public function view(User $user, User $target): bool
    {
        return $user->canManageUsers() || $user->is($target);
    }

    public function create(User $user): bool
    {
        return $user->canManageUsers();
    }

    public function update(User $user, User $target): bool
    {
        return $user->canManageUsers();
    }

    public function delete(User $user, User $target): bool
    {
        return $user->canManageUsers();
    }

    public function approve(User $user, User $target): bool
    {
        return $user->canApproveUsers();
    }

    public function suspend(User $user, User $target): bool
    {
        if (! $user->canManageUsers()) {
            return false;
        }

        if ($user->is($target)) {
            return false;
        }

        if ($target->isSuperAdmin() && User::where('role', UserRole::SuperAdmin)->count() === 1) {
            return false;
        }

        return true;
    }

    public function reactivate(User $user, User $target): bool
    {
        return $user->canManageUsers();
    }
}
