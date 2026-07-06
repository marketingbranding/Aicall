<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->sales = User::factory()->sales()->create();
    }

    public function test_super_admin_can_access_hq(): void
    {
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('access-hq'));
    }

    public function test_sales_cannot_access_hq(): void
    {
        $this->assertFalse(Gate::forUser($this->sales)->allows('access-hq'));
    }

    public function test_super_admin_can_manage_branches(): void
    {
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('manage-branches'));
    }

    public function test_sales_cannot_manage_branches(): void
    {
        $this->assertFalse(Gate::forUser($this->sales)->allows('manage-branches'));
    }

    public function test_super_admin_can_manage_users(): void
    {
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('manage-users'));
    }

    public function test_sales_cannot_manage_users(): void
    {
        $this->assertFalse(Gate::forUser($this->sales)->allows('manage-users'));
    }

    public function test_super_admin_can_approve_users(): void
    {
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('approve-users'));
    }

    public function test_sales_cannot_approve_users(): void
    {
        $this->assertFalse(Gate::forUser($this->sales)->allows('approve-users'));
    }

    public function test_super_admin_can_manage_personas(): void
    {
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('manage-personas'));
    }

    public function test_sales_cannot_manage_personas(): void
    {
        $this->assertFalse(Gate::forUser($this->sales)->allows('manage-personas'));
    }

    public function test_super_admin_can_manage_scenarios(): void
    {
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('manage-scenarios'));
    }

    public function test_sales_cannot_manage_scenarios(): void
    {
        $this->assertFalse(Gate::forUser($this->sales)->allows('manage-scenarios'));
    }

    public function test_super_admin_can_configure_ai_providers(): void
    {
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('configure-ai-providers'));
    }

    public function test_sales_cannot_configure_ai_providers(): void
    {
        $this->assertFalse(Gate::forUser($this->sales)->allows('configure-ai-providers'));
    }

    public function test_sales_can_view_own_training(): void
    {
        $this->assertTrue(Gate::forUser($this->sales)->allows('view-own-training'));
    }

    public function test_super_admin_can_view_all_training_sessions(): void
    {
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('view-all-training-sessions'));
    }

    public function test_sales_cannot_view_all_training_sessions(): void
    {
        $this->assertFalse(Gate::forUser($this->sales)->allows('view-all-training-sessions'));
    }

    public function test_super_admin_can_create_branch(): void
    {
        $this->assertTrue($this->superAdmin->can('create', Branch::class));
    }

    public function test_sales_cannot_create_branch(): void
    {
        $this->assertFalse($this->sales->can('create', Branch::class));
    }

    public function test_super_admin_can_update_branch(): void
    {
        $branch = Branch::factory()->create();

        $this->assertTrue($this->superAdmin->can('update', $branch));
    }

    public function test_sales_cannot_update_branch(): void
    {
        $branch = Branch::factory()->create();

        $this->assertFalse($this->sales->can('update', $branch));
    }

    public function test_super_admin_can_delete_branch(): void
    {
        $branch = Branch::factory()->create();

        $this->assertTrue($this->superAdmin->can('delete', $branch));
    }

    public function test_sales_cannot_delete_branch(): void
    {
        $branch = Branch::factory()->create();

        $this->assertFalse($this->sales->can('delete', $branch));
    }

    public function test_super_admin_can_view_any_user(): void
    {
        $this->assertTrue($this->superAdmin->can('viewAny', User::class));
    }

    public function test_sales_cannot_view_any_user(): void
    {
        $this->assertFalse($this->sales->can('viewAny', User::class));
    }

    public function test_super_admin_can_view_user(): void
    {
        $target = User::factory()->sales()->create();

        $this->assertTrue($this->superAdmin->can('view', $target));
    }

    public function test_sales_cannot_view_other_user(): void
    {
        $other = User::factory()->sales()->create();

        $this->assertFalse($this->sales->can('view', $other));
    }

    public function test_sales_can_view_self(): void
    {
        $this->assertTrue($this->sales->can('view', $this->sales));
    }

    public function test_super_admin_can_approve_user(): void
    {
        $pending = User::factory()->create(['status' => User::STATUS_PENDING_APPROVAL]);

        $this->assertTrue($this->superAdmin->can('approve', $pending));
    }

    public function test_sales_cannot_approve_user(): void
    {
        $pending = User::factory()->create(['status' => User::STATUS_PENDING_APPROVAL]);

        $this->assertFalse($this->sales->can('approve', $pending));
    }

    public function test_hq_middleware_allows_super_admin(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get('/_test/hq-ping');

        $response->assertOk();
    }

    public function test_hq_middleware_rejects_sales(): void
    {
        $this->actingAs($this->sales);

        $response = $this->get('/_test/hq-ping');

        $response->assertForbidden();
    }

    public function test_hq_middleware_rejects_guest(): void
    {
        $response = $this->get('/_test/hq-ping');

        $response->assertRedirect(route('login'));
    }

    public function test_authorization_is_centralized_in_user_role_enum(): void
    {
        $this->assertTrue(method_exists(UserRole::class, 'canAccessHq'));
        $this->assertTrue(method_exists(UserRole::class, 'canManageBranches'));
        $this->assertTrue(method_exists(UserRole::class, 'canManageUsers'));
        $this->assertTrue(method_exists(UserRole::class, 'canApproveUsers'));
        $this->assertTrue(method_exists(UserRole::class, 'canManagePersonas'));
        $this->assertTrue(method_exists(UserRole::class, 'canManageScenarios'));
        $this->assertTrue(method_exists(UserRole::class, 'canConfigureAiProviders'));
        $this->assertTrue(method_exists(UserRole::class, 'canViewAllTrainingSessions'));
    }

    public function test_branch_policy_methods_exist(): void
    {
        $branch = Branch::factory()->create();

        $this->assertTrue(method_exists(\App\Policies\BranchPolicy::class, 'viewAny'));
        $this->assertTrue(method_exists(\App\Policies\BranchPolicy::class, 'view'));
        $this->assertTrue(method_exists(\App\Policies\BranchPolicy::class, 'create'));
        $this->assertTrue(method_exists(\App\Policies\BranchPolicy::class, 'update'));
        $this->assertTrue(method_exists(\App\Policies\BranchPolicy::class, 'delete'));
    }

    public function test_user_policy_methods_exist(): void
    {
        $this->assertTrue(method_exists(\App\Policies\UserPolicy::class, 'viewAny'));
        $this->assertTrue(method_exists(\App\Policies\UserPolicy::class, 'view'));
        $this->assertTrue(method_exists(\App\Policies\UserPolicy::class, 'create'));
        $this->assertTrue(method_exists(\App\Policies\UserPolicy::class, 'update'));
        $this->assertTrue(method_exists(\App\Policies\UserPolicy::class, 'delete'));
        $this->assertTrue(method_exists(\App\Policies\UserPolicy::class, 'approve'));
    }
}
