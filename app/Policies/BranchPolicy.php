<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageBranches();
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->canManageBranches();
    }

    public function create(User $user): bool
    {
        return $user->canManageBranches();
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->canManageBranches();
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->canManageBranches();
    }
}
