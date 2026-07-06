<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\User;
use App\Policies\BranchPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Branch::class => BranchPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        Gate::define('access-hq', fn (User $user) => $user->canAccessHq());

        Gate::define('manage-branches', fn (User $user) => $user->canManageBranches());

        Gate::define('manage-users', fn (User $user) => $user->canManageUsers());

        Gate::define('approve-users', fn (User $user) => $user->canApproveUsers());

        Gate::define('manage-personas', fn (User $user) => $user->canManagePersonas());

        Gate::define('manage-scenarios', fn (User $user) => $user->canManageScenarios());

        Gate::define('configure-ai-providers', fn (User $user) => $user->canConfigureAiProviders());

        Gate::define('view-own-training', fn (User $user) => $user->isActive());

        Gate::define('view-all-training-sessions', fn (User $user) => $user->canViewAllTrainingSessions());
    }
}
