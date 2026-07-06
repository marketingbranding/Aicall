<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HqPendingUserListTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_pending_user_list(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        User::factory()->pendingApproval()->sales()->create([
            'name' => 'Ahmad Subianto',
            'email' => 'ahmad@example.com',
        ]);

        $response = $this->actingAs($superAdmin)
            ->get(route('hq.users.pending'));

        $response->assertOk();
        $response->assertSee('Ahmad Subianto');
        $response->assertSee('ahmad@example.com');
        $response->assertSee('Pengguna Menunggu Persetujuan');
    }

    public function test_sales_user_cannot_view_pending_user_list(): void
    {
        $sales = User::factory()->sales()->active()->create();

        $response = $this->actingAs($sales)
            ->get(route('hq.users.pending'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_pending_user_list(): void
    {
        $response = $this->get(route('hq.users.pending'));

        $response->assertRedirect(route('login'));
    }

    public function test_pending_users_appear_in_list(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $pendingUser = User::factory()->pendingApproval()->sales()->create([
            'name' => 'Dewi Sartika',
            'email' => 'dewi@example.com',
        ]);

        $response = $this->actingAs($superAdmin)
            ->get(route('hq.users.pending'));

        $response->assertOk();
        $response->assertSee('Dewi Sartika');
        $response->assertSee('dewi@example.com');
    }

    public function test_active_users_do_not_appear_in_list(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        User::factory()->sales()->active()->create([
            'name' => 'Active User',
            'email' => 'active@example.com',
        ]);

        $response = $this->actingAs($superAdmin)
            ->get(route('hq.users.pending'));

        $response->assertOk();
        $response->assertDontSee('Active User');
        $response->assertDontSee('active@example.com');
    }

    public function test_suspended_users_do_not_appear_in_list(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        User::factory()->sales()->suspended()->create([
            'name' => 'Suspended User',
            'email' => 'suspended@example.com',
        ]);

        $response = $this->actingAs($superAdmin)
            ->get(route('hq.users.pending'));

        $response->assertOk();
        $response->assertDontSee('Suspended User');
        $response->assertDontSee('suspended@example.com');
    }

    public function test_empty_state_when_no_pending_users(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)
            ->get(route('hq.users.pending'));

        $response->assertOk();
        $response->assertSee('Tidak ada pengguna yang menunggu persetujuan.');
    }

    public function test_super_admin_can_access_hq_nav_link(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)
            ->get(route('dashboard'));

        $response->assertSee('HQ');
    }

    public function test_sales_cannot_see_hq_nav_link(): void
    {
        $sales = User::factory()->sales()->active()->create();

        $response = $this->actingAs($sales)
            ->get(route('dashboard'));

        $response->assertDontSee('HQ');
    }
}
