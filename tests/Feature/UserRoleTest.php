<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_be_created(): void
    {
        $user = User::factory()->superAdmin()->create([
            'email' => 'admin@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => UserRole::SuperAdmin->value,
            'status' => User::STATUS_ACTIVE,
            'branch_id' => null,
        ]);
        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_role_helpers_work(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $sales = User::factory()->sales()->create();

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($superAdmin->isSales());
        $this->assertTrue($superAdmin->canAccessHq());

        $this->assertTrue($sales->isSales());
        $this->assertFalse($sales->isSuperAdmin());
        $this->assertFalse($sales->canAccessHq());
    }

    public function test_future_role_architecture_is_centralized(): void
    {
        $this->assertSame('SUPER_ADMIN', UserRole::SuperAdmin->value);
        $this->assertSame('SALES', UserRole::Sales->value);
        $this->assertTrue(method_exists(UserRole::class, 'canAccessHq'));
    }
}
