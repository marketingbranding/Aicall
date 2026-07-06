<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountStatusAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_user_cannot_access_protected_training_area(): void
    {
        $user = User::factory()->pendingApproval()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('account.pending', absolute: false));
    }

    public function test_suspended_user_cannot_access_protected_training_area(): void
    {
        $user = User::factory()->suspended()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('account.suspended', absolute: false));
    }

    public function test_active_user_can_access_allowed_area(): void
    {
        $user = User::factory()->active()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }

    public function test_pending_user_can_see_waiting_approval_page(): void
    {
        $user = User::factory()->pendingApproval()->create();

        $response = $this->actingAs($user)->get(route('account.pending'));

        $response->assertOk();
        $response->assertSee('Menunggu Persetujuan HQ');
    }

    public function test_suspended_user_can_see_suspended_account_page(): void
    {
        $user = User::factory()->suspended()->create();

        $response = $this->actingAs($user)->get(route('account.suspended'));

        $response->assertOk();
        $response->assertSee('Akun Ditangguhkan');
    }
}
