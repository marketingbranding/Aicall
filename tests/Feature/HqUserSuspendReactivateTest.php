<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HqUserSuspendReactivateTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->branch = Branch::factory()->create();
    }

    private function createActiveSalesUser(): User
    {
        return User::factory()->sales()->active()->create([
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_super_admin_can_suspend_active_sales_user(): void
    {
        $user = $this->createActiveSalesUser();

        $response = $this->actingAs($this->superAdmin)
            ->from(route('hq.users.pending'))
            ->post(route('hq.users.suspend', $user));

        $response->assertRedirect(route('hq.users.pending'));
        $response->assertSessionHas('success');

        $user->refresh();

        $this->assertTrue($user->isSuspended());
    }

    public function test_super_admin_can_reactivate_suspended_sales_user(): void
    {
        $user = $this->createActiveSalesUser();
        $user->suspend();
        $user->refresh();
        $this->assertTrue($user->isSuspended());

        $response = $this->actingAs($this->superAdmin)
            ->from(route('hq.users.pending'))
            ->post(route('hq.users.reactivate', $user));

        $response->assertRedirect(route('hq.users.pending'));
        $response->assertSessionHas('success');

        $user->refresh();

        $this->assertTrue($user->isActive());
    }

    public function test_sales_user_cannot_suspend(): void
    {
        $user = $this->createActiveSalesUser();
        $sales = User::factory()->sales()->active()->create();

        $response = $this->actingAs($sales)
            ->post(route('hq.users.suspend', $user));

        $response->assertForbidden();

        $user->refresh();
        $this->assertTrue($user->isActive());
    }

    public function test_sales_user_cannot_reactivate(): void
    {
        $user = $this->createActiveSalesUser();
        $user->suspend();
        $user->refresh();

        $sales = User::factory()->sales()->active()->create();

        $response = $this->actingAs($sales)
            ->post(route('hq.users.reactivate', $user));

        $response->assertForbidden();

        $user->refresh();
        $this->assertTrue($user->isSuspended());
    }

    public function test_guest_cannot_suspend(): void
    {
        $user = $this->createActiveSalesUser();

        $response = $this->post(route('hq.users.suspend', $user));

        $response->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue($user->isActive());
    }

    public function test_guest_cannot_reactivate(): void
    {
        $user = $this->createActiveSalesUser();
        $user->suspend();
        $user->refresh();

        $response = $this->post(route('hq.users.reactivate', $user));

        $response->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue($user->isSuspended());
    }

    public function test_suspended_user_cannot_access_dashboard(): void
    {
        $user = $this->createActiveSalesUser();
        $user->suspend();
        $user->refresh();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('account.suspended', absolute: false));
    }

    public function test_cannot_suspend_self(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.users.suspend', $this->superAdmin));

        $response->assertForbidden();

        $this->superAdmin->refresh();
        $this->assertTrue($this->superAdmin->isActive());
    }

    public function test_cannot_suspend_the_only_super_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.users.suspend', $this->superAdmin));

        $response->assertForbidden();
    }

    public function test_can_suspend_other_super_admin_when_multiple_exist(): void
    {
        $otherAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($this->superAdmin)
            ->from(route('hq.users.pending'))
            ->post(route('hq.users.suspend', $otherAdmin));

        $response->assertRedirect(route('hq.users.pending'));
        $response->assertSessionHas('success');

        $otherAdmin->refresh();
        $this->assertTrue($otherAdmin->isSuspended());
    }

    public function test_suspend_method_exists_on_user_model(): void
    {
        $this->assertTrue(method_exists(User::class, 'suspend'));
        $this->assertTrue(method_exists(User::class, 'reactivate'));
    }

    public function test_suspend_policy_methods_exist(): void
    {
        $this->assertTrue(method_exists(\App\Policies\UserPolicy::class, 'suspend'));
        $this->assertTrue(method_exists(\App\Policies\UserPolicy::class, 'reactivate'));
    }

    public function test_super_admin_can_suspend_active_sales_user_and_user_appears_suspended_in_list(): void
    {
        $user = $this->createActiveSalesUser();

        $this->actingAs($this->superAdmin)
            ->post(route('hq.users.suspend', $user));

        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.users.pending'));

        $response->assertOk();
        $response->assertSee('Ditangguhkan');
        $response->assertDontSee('Tangguhkan');
    }
}
