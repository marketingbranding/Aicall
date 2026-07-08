<?php

namespace Tests\Feature;

use App\Enums\EndingType;
use App\Enums\RoleplaySessionStatus;
use App\Models\RoleplaySession;
use App\Models\RoleplaySessionSnapshot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleplaySessionStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_to_valid_transition(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::CREATED);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'PREPARING',
            ])
            ->assertOk()
            ->assertJsonPath('session.status', 'PREPARING');

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'READY',
            ])
            ->assertOk()
            ->assertJsonPath('session.status', 'READY');

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'ACTIVE',
            ])
            ->assertOk()
            ->assertJsonPath('session.status', 'ACTIVE');

        $session->refresh();
        $this->assertNotNull($session->started_at);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'ENDING',
            ])
            ->assertOk()
            ->assertJsonPath('session.status', 'ENDING');

        $session->refresh();
        $this->assertNotNull($session->ended_at);
        $this->assertSame(EndingType::USER_END->value, $session->ending_type);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'COMPLETED',
            ])
            ->assertOk()
            ->assertJsonPath('session.status', 'COMPLETED');
    }

    public function test_another_user_cannot_update_session(): void
    {
        $owner = User::factory()->sales()->active()->create();
        $other = User::factory()->sales()->active()->create();
        $session = $this->createSession($owner, RoleplaySessionStatus::CREATED);

        $this->actingAs($other)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'PREPARING',
            ])
            ->assertNotFound();
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::CREATED);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'ACTIVE',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'invalid_transition');
    }

    public function test_time_limit_stores_time_limit_ending_type(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::ACTIVE);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'ENDING',
                'ending_type' => 'TIME_LIMIT',
            ])
            ->assertOk()
            ->assertJsonPath('session.ending_type', 'TIME_LIMIT');

        $session->refresh();
        $this->assertSame(EndingType::TIME_LIMIT->value, $session->ending_type);
        $this->assertNotNull($session->ended_at);
    }

    public function test_user_stop_stores_user_end_ending_type(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::ACTIVE);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'ENDING',
                'ending_type' => 'USER_END',
            ])
            ->assertOk()
            ->assertJsonPath('session.ending_type', 'USER_END');

        $session->refresh();
        $this->assertSame(EndingType::USER_END->value, $session->ending_type);
    }

    public function test_failure_stores_failure_ending_type(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::ACTIVE);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'FAILED',
                'ending_reason' => 'Gemini Live connection lost',
            ])
            ->assertOk()
            ->assertJsonPath('session.status', 'FAILED')
            ->assertJsonPath('session.ending_type', 'FAILURE');

        $session->refresh();
        $this->assertSame(EndingType::FAILURE->value, $session->ending_type);
        $this->assertSame('Gemini Live connection lost', $session->ending_reason);
        $this->assertNotNull($session->ended_at);
    }

    public function test_completed_session_cannot_be_mutated(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::COMPLETED);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'ENDING',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'invalid_transition');

        $session->refresh();
        $this->assertSame(RoleplaySessionStatus::COMPLETED->value, $session->status);
    }

    public function test_failed_session_cannot_be_mutated(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::FAILED);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'ENDING',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'invalid_transition');

        $session->refresh();
        $this->assertSame(RoleplaySessionStatus::FAILED->value, $session->status);
    }

    public function test_ending_defaults_to_user_end(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::ACTIVE);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'ENDING',
            ])
            ->assertOk()
            ->assertJsonPath('session.ending_type', 'USER_END');
    }

    public function test_ending_can_include_reason(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::ACTIVE);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'ENDING',
                'ending_type' => 'USER_END',
                'ending_reason' => 'User clicked stop button',
            ])
            ->assertOk()
            ->assertJsonPath('session.ending_reason', 'User clicked stop button');
    }

    public function test_started_at_is_set_on_first_active_transition(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::READY);

        Carbon::setTestNow('2026-07-08 12:00:00');

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'ACTIVE',
            ])
            ->assertOk();

        $session->refresh();
        $this->assertNotNull($session->started_at);
        $this->assertSame('2026-07-08 12:00:00', $session->started_at->format('Y-m-d H:i:s'));
    }

    public function test_started_at_is_not_overwritten_on_subsequent_active_updates(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::READY);

        Carbon::setTestNow('2026-07-08 12:00:00');
        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'ACTIVE',
            ])->assertOk();

        Carbon::setTestNow('2026-07-08 12:05:00');
        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'ACTIVE',
            ])->assertOk();

        $session->refresh();
        $this->assertSame('2026-07-08 12:00:00', $session->started_at->format('Y-m-d H:i:s'));
    }

    public function test_ready_to_reconnecting_is_allowed(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::READY);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'RECONNECTING',
            ])
            ->assertOk()
            ->assertJsonPath('session.status', 'RECONNECTING');
    }

    public function test_active_to_reconnecting_is_allowed(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::ACTIVE);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'RECONNECTING',
            ])
            ->assertOk()
            ->assertJsonPath('session.status', 'RECONNECTING');
    }

    public function test_reconnecting_to_active_preserves_started_at(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::RECONNECTING);
        $session->update(['started_at' => '2026-07-08 12:00:00']);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'ACTIVE',
            ])
            ->assertOk()
            ->assertJsonPath('session.status', 'ACTIVE');

        $session->refresh();
        $this->assertSame('2026-07-08 12:00:00', $session->started_at->format('Y-m-d H:i:s'));
        $this->assertNull($session->ended_at);
    }

    public function test_reconnecting_to_failed_stores_failure_ending(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::RECONNECTING);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'FAILED',
                'ending_reason' => 'Reconnection failed',
            ])
            ->assertOk()
            ->assertJsonPath('session.status', 'FAILED')
            ->assertJsonPath('session.ending_type', 'FAILURE');

        $session->refresh();
        $this->assertSame(EndingType::FAILURE->value, $session->ending_type);
        $this->assertSame('Reconnection failed', $session->ending_reason);
        $this->assertNotNull($session->ended_at);
    }

    public function test_reconnecting_self_transition_is_allowed(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::RECONNECTING);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'RECONNECTING',
            ])
            ->assertOk()
            ->assertJsonPath('session.status', 'RECONNECTING');
    }

    public function test_created_to_reconnecting_is_rejected(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::CREATED);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'RECONNECTING',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'invalid_transition');
    }

    public function test_reconnecting_to_ending_is_rejected(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::RECONNECTING);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'ENDING',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'invalid_transition');
    }

    public function test_invalid_status_value_rejected(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::CREATED);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'INVALID_STATUS',
            ])
            ->assertStatus(422);
    }

    public function test_invalid_ending_type_rejected(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSession($user, RoleplaySessionStatus::ACTIVE);

        $this->actingAs($user)
            ->patchJson(route('training.sessions.status.update', $session->public_id), [
                'status' => 'ENDING',
                'ending_type' => 'INVALID_END',
            ])
            ->assertStatus(422);
    }

    private function createSession(User $user, RoleplaySessionStatus $status): RoleplaySession
    {
        $session = RoleplaySession::factory()
            ->forUser($user)
            ->status($status)
            ->create();

        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
        ]);

        return $session;
    }
}
