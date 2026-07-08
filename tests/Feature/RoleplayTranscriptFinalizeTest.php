<?php

namespace Tests\Feature;

use App\Enums\RoleplaySessionStatus;
use App\Enums\TranscriptIntegrity;
use App\Models\RoleplaySession;
use App\Models\RoleplaySessionSnapshot;
use App\Models\RoleplayTranscriptTurn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleplayTranscriptFinalizeTest extends TestCase
{
    use RefreshDatabase;

    private function endingSession(User $user): RoleplaySession
    {
        $session = RoleplaySession::factory()
            ->forUser($user)
            ->status(RoleplaySessionStatus::ENDING)
            ->create([
                'ended_at' => now(),
            ]);

        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
        ]);

        return $session;
    }

    private function finalizeUrl(RoleplaySession $session): string
    {
        return route('training.sessions.transcript.finalize', $session->public_id);
    }

    public function test_owner_can_finalize_transcript(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->endingSession($user);

        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'AI',
            'text' => 'Selamat siang.',
            'status' => 'FINAL',
        ]);
        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 1,
            'speaker' => 'USER',
            'text' => 'Halo.',
            'status' => 'FINAL',
        ]);

        $response = $this->actingAs($user)
            ->postJson($this->finalizeUrl($session));

        $response->assertOk()
            ->assertJsonPath('integrity', TranscriptIntegrity::COMPLETE->value)
            ->assertJsonPath('turn_count', 2)
            ->assertJsonPath('session_status', RoleplaySessionStatus::TRANSCRIPT_FINALIZING->value);

        $session->refresh();
        $this->assertEquals(RoleplaySessionStatus::TRANSCRIPT_FINALIZING->value, $session->status);
        $this->assertEquals(TranscriptIntegrity::COMPLETE->value, $session->transcript_integrity);
    }

    public function test_another_user_cannot_finalize(): void
    {
        $owner = User::factory()->sales()->active()->create();
        $other = User::factory()->sales()->active()->create();
        $session = $this->endingSession($owner);

        $response = $this->actingAs($other)
            ->postJson($this->finalizeUrl($session));

        $response->assertNotFound();
    }

    public function test_pending_user_cannot_finalize(): void
    {
        $user = User::factory()->sales()->pendingApproval()->create();
        $session = $this->endingSession($user);

        $response = $this->actingAs($user)
            ->postJson($this->finalizeUrl($session));

        $response->assertForbidden();
    }

    public function test_suspended_user_cannot_finalize(): void
    {
        $user = User::factory()->sales()->suspended()->create();
        $session = $this->endingSession($user);

        $response = $this->actingAs($user)
            ->postJson($this->finalizeUrl($session));

        $response->assertForbidden();
    }

    public function test_repeated_finalization_is_safe(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->endingSession($user);

        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'USER',
            'text' => 'Halo.',
            'status' => 'FINAL',
        ]);

        $this->actingAs($user)
            ->postJson($this->finalizeUrl($session))
            ->assertOk()
            ->assertJsonPath('integrity', TranscriptIntegrity::COMPLETE->value);

        $this->actingAs($user)
            ->postJson($this->finalizeUrl($session))
            ->assertOk()
            ->assertJsonPath('integrity', TranscriptIntegrity::COMPLETE->value)
            ->assertJsonPath('turn_count', 1)
            ->assertJsonPath('session_status', RoleplaySessionStatus::TRANSCRIPT_FINALIZING->value);

        $session->refresh();
        $this->assertEquals(RoleplaySessionStatus::TRANSCRIPT_FINALIZING->value, $session->status);
        $this->assertDatabaseCount('roleplay_transcript_turns', 1);
    }

    public function test_finalization_computes_complete(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->endingSession($user);

        for ($i = 0; $i < 3; $i++) {
            RoleplayTranscriptTurn::factory()->create([
                'roleplay_session_id' => $session->id,
                'sequence' => $i,
                'speaker' => $i % 2 === 0 ? 'AI' : 'USER',
                'text' => "Teks percakapan sekuen $i.",
                'status' => 'FINAL',
            ]);
        }

        $response = $this->actingAs($user)
            ->postJson($this->finalizeUrl($session));

        $response->assertOk()
            ->assertJsonPath('integrity', TranscriptIntegrity::COMPLETE->value)
            ->assertJsonPath('turn_count', 3)
            ->assertJsonPath('issues', []);
    }

    public function test_finalization_computes_partial(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->endingSession($user);

        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'AI',
            'text' => 'Selamat siang.',
            'status' => 'PARTIAL',
        ]);

        $response = $this->actingAs($user)
            ->postJson($this->finalizeUrl($session));

        $response->assertOk()
            ->assertJsonPath('integrity', TranscriptIntegrity::PARTIAL->value)
            ->assertJsonPath('turn_count', 1);
    }

    public function test_finalization_computes_failed_when_no_turns(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->endingSession($user);

        $response = $this->actingAs($user)
            ->postJson($this->finalizeUrl($session));

        $response->assertOk()
            ->assertJsonPath('integrity', TranscriptIntegrity::FAILED->value)
            ->assertJsonPath('turn_count', 0)
            ->assertJsonPath('session_status', RoleplaySessionStatus::TRANSCRIPT_FINALIZING->value);
    }

    public function test_transcript_submission_after_finalization_is_rejected(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->endingSession($user);

        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'USER',
            'text' => 'Halo.',
            'status' => 'FINAL',
        ]);

        $this->actingAs($user)
            ->postJson($this->finalizeUrl($session))
            ->assertOk();

        $response = $this->actingAs($user)
            ->postJson(
                route('training.sessions.transcript.store', $session->public_id),
                [
                    'sequence' => 1,
                    'speaker' => 'AI',
                    'text' => 'Halo juga.',
                    'status' => 'FINAL',
                    'started_at' => now()->toIso8601String(),
                ],
            );

        $response->assertStatus(409)
            ->assertJsonPath('error', 'invalid_session_status');
    }

    public function test_finalization_from_active_is_rejected(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()
            ->forUser($user)
            ->active()
            ->create();

        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson($this->finalizeUrl($session));

        $response->assertStatus(409)
            ->assertJsonPath('error', 'invalid_session_status');
    }

    public function test_finalization_from_completed_is_idempotent(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()
            ->forUser($user)
            ->completed()
            ->create();

        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
        ]);

        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'USER',
            'text' => 'Halo.',
            'status' => 'FINAL',
        ]);

        $response = $this->actingAs($user)
            ->postJson($this->finalizeUrl($session));

        $response->assertOk()
            ->assertJsonPath('integrity', TranscriptIntegrity::COMPLETE->value)
            ->assertJsonPath('session_status', RoleplaySessionStatus::COMPLETED->value);
    }
}
