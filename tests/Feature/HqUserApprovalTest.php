<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HqUserApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $pendingUser;
    private Branch $activeBranch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->pendingUser = User::factory()->pendingApproval()->sales()->create();
        $this->activeBranch = Branch::factory()->create();
    }

    public function test_super_admin_can_approve_pending_sales_user_with_active_branch(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->from(route('hq.users.pending'))
            ->post(route('hq.users.approve', $this->pendingUser), [
                'branch_id' => $this->activeBranch->id,
            ]);

        $response->assertRedirect(route('hq.users.pending'));
        $response->assertSessionHas('success');

        $this->pendingUser->refresh();

        $this->assertTrue($this->pendingUser->isActive());
        $this->assertEquals($this->activeBranch->id, $this->pendingUser->branch_id);
        $this->assertNotNull($this->pendingUser->approved_at);
        $this->assertEquals($this->superAdmin->id, $this->pendingUser->approved_by);
    }

    public function test_approval_requires_branch(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->from(route('hq.users.pending'))
            ->post(route('hq.users.approve', $this->pendingUser), [
                'branch_id' => '',
            ]);

        $response->assertRedirect(route('hq.users.pending'));
        $response->assertSessionHasErrors('branch_id');

        $this->pendingUser->refresh();

        $this->assertTrue($this->pendingUser->isPendingApproval());
    }

    public function test_inactive_branch_cannot_be_assigned(): void
    {
        $inactiveBranch = Branch::factory()->inactive()->create();

        $response = $this->actingAs($this->superAdmin)
            ->from(route('hq.users.pending'))
            ->post(route('hq.users.approve', $this->pendingUser), [
                'branch_id' => $inactiveBranch->id,
            ]);

        $response->assertRedirect(route('hq.users.pending'));
        $response->assertSessionHasErrors('branch_id');

        $this->pendingUser->refresh();

        $this->assertTrue($this->pendingUser->isPendingApproval());
    }

    public function test_sales_user_cannot_approve(): void
    {
        $sales = User::factory()->sales()->active()->create();

        $response = $this->actingAs($sales)
            ->post(route('hq.users.approve', $this->pendingUser), [
                'branch_id' => $this->activeBranch->id,
            ]);

        $response->assertForbidden();

        $this->pendingUser->refresh();

        $this->assertTrue($this->pendingUser->isPendingApproval());
    }

    public function test_guest_cannot_approve(): void
    {
        $response = $this->post(route('hq.users.approve', $this->pendingUser), [
            'branch_id' => $this->activeBranch->id,
        ]);

        $response->assertRedirect(route('login'));

        $this->pendingUser->refresh();

        $this->assertTrue($this->pendingUser->isPendingApproval());
    }

    public function test_approved_user_becomes_active_and_has_branch_id(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('hq.users.approve', $this->pendingUser), [
                'branch_id' => $this->activeBranch->id,
            ]);

        $this->pendingUser->refresh();

        $this->assertTrue($this->pendingUser->isActive());
        $this->assertEquals($this->activeBranch->id, $this->pendingUser->branch_id);
        $this->assertNotNull($this->pendingUser->approved_at);
        $this->assertEquals($this->superAdmin->id, $this->pendingUser->approved_by);
    }

    public function test_pending_user_list_no_longer_shows_approved_user_in_pending_section(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('hq.users.approve', $this->pendingUser), [
                'branch_id' => $this->activeBranch->id,
            ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.users.pending'));

        $response->assertOk();
        $response->assertSee('Pengguna Aktif');
        $response->assertSee($this->pendingUser->name);
        $response->assertSee('Tidak ada pengguna yang menunggu persetujuan.');
    }

    public function test_approve_method_exists_on_user_model(): void
    {
        $this->assertTrue(method_exists(User::class, 'approve'));
        $this->assertTrue(method_exists(User::class, 'approvedBy'));
    }
}
