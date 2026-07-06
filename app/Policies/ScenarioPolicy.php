<?php

namespace App\Policies;

use App\Models\Scenario;
use App\Models\User;

class ScenarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageScenarios();
    }

    public function view(User $user, Scenario $scenario): bool
    {
        return $user->canManageScenarios();
    }

    public function create(User $user): bool
    {
        return $user->canManageScenarios();
    }

    public function update(User $user, Scenario $scenario): bool
    {
        return $user->canManageScenarios() && $scenario->isActive();
    }

    public function delete(User $user, Scenario $scenario): bool
    {
        return $user->canManageScenarios();
    }

    public function archive(User $user, Scenario $scenario): bool
    {
        return $user->canManageScenarios() && $scenario->isActive();
    }

    public function duplicate(User $user, Scenario $scenario): bool
    {
        return $user->canManageScenarios();
    }
}
