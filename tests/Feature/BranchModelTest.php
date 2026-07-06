<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_can_be_created(): void
    {
        $branch = Branch::create([
            'code' => 'BDG_01',
            'name' => 'Bandung Barat',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('branches', [
            'code' => 'BDG_01',
            'name' => 'Bandung Barat',
            'is_active' => true,
        ]);

        $this->assertTrue($branch->is_active);
    }

    public function test_user_can_belong_to_branch(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->forBranch($branch)->create();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'branch_id' => $branch->id,
        ]);
    }

    public function test_branch_relationship_works(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->forBranch($branch)->create();

        $this->assertTrue($user->branch->is($branch));
        $this->assertTrue($branch->users->contains($user));
    }

    public function test_user_branch_is_nullable(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->branch_id);
        $this->assertNull($user->branch);
    }
}
