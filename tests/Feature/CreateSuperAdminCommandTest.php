<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_super_admin(): void
    {
        $this->artisan('app:create-super-admin', [
            '--name' => 'Admin Utama',
            '--email' => 'admin@example.com',
            '--password' => 'secure-password-123',
        ])->assertSuccessful();

        $user = User::where('email', 'admin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Admin Utama', $user->name);
        $this->assertSame(UserRole::SuperAdmin, $user->role);
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertNull($user->branch_id);
    }

    public function test_created_super_admin_can_login(): void
    {
        $this->artisan('app:create-super-admin', [
            '--name' => 'Admin Login',
            '--email' => 'login-test@example.com',
            '--password' => 'secure-password-123',
        ])->assertSuccessful();

        $this->post('/login', [
            'email' => 'login-test@example.com',
            'password' => 'secure-password-123',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->artisan('app:create-super-admin', [
            '--name' => 'Duplicate',
            '--email' => 'existing@example.com',
            '--password' => 'secure-password-123',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', [
            'email' => 'existing@example.com',
            'role' => UserRole::SuperAdmin->value,
        ]);
    }

    public function test_duplicate_super_admin_is_rejected_without_force(): void
    {
        User::factory()->superAdmin()->create(['email' => 'first-admin@example.com']);

        $this->artisan('app:create-super-admin', [
            '--name' => 'Second Admin',
            '--email' => 'second-admin@example.com',
            '--password' => 'secure-password-123',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', [
            'email' => 'second-admin@example.com',
        ]);
    }

    public function test_force_allows_second_super_admin(): void
    {
        User::factory()->superAdmin()->create(['email' => 'first-admin@example.com']);

        $this->artisan('app:create-super-admin', [
            '--name' => 'Second Admin',
            '--email' => 'second-admin@example.com',
            '--password' => 'secure-password-123',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'second-admin@example.com',
            'role' => UserRole::SuperAdmin->value,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    public function test_missing_name_is_rejected(): void
    {
        $this->artisan('app:create-super-admin', [
            '--email' => 'admin@example.com',
            '--password' => 'secure-password-123',
        ])->assertFailed();
    }

    public function test_missing_email_is_rejected(): void
    {
        $this->artisan('app:create-super-admin', [
            '--name' => 'Admin',
            '--password' => 'secure-password-123',
        ])->assertFailed();
    }

    public function test_missing_password_is_rejected(): void
    {
        $this->artisan('app:create-super-admin', [
            '--name' => 'Admin',
            '--email' => 'admin@example.com',
        ])->assertFailed();
    }

    public function test_invalid_email_is_rejected(): void
    {
        $this->artisan('app:create-super-admin', [
            '--name' => 'Admin',
            '--email' => 'not-an-email',
            '--password' => 'secure-password-123',
        ])->assertFailed();
    }

    public function test_short_password_is_rejected(): void
    {
        $this->artisan('app:create-super-admin', [
            '--name' => 'Admin',
            '--email' => 'admin@example.com',
            '--password' => 'short',
        ])->assertFailed();
    }

    public function test_created_super_admin_is_active_and_super_admin(): void
    {
        $this->artisan('app:create-super-admin', [
            '--name' => 'Status Check',
            '--email' => 'status-check@example.com',
            '--password' => 'secure-password-123',
        ])->assertSuccessful();

        $user = User::where('email', 'status-check@example.com')->first();

        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isPendingApproval());
        $this->assertFalse($user->isSuspended());
        $this->assertTrue($user->canAccessHq());
    }
}
