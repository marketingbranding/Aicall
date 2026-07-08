<?php

namespace Tests\Feature;

use App\Enums\RoleplaySessionStatus;
use App\Models\RoleplaySession;
use App\Models\RoleplaySessionSnapshot;
use App\Models\RoleplayTranscriptTurn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleplayTranscriptTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveSession(User $user): RoleplaySession
    {
        $session = RoleplaySession::factory()
            ->forUser($user)
            ->status(RoleplaySessionStatus::ACTIVE)
            ->create();

        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
        ]);

        return $session;
    }

    private function validPayload(int $sequence = 0, array $overrides = []): array
    {
        return array_merge([
            'sequence' => $sequence,
            'speaker' => 'USER',
            'text' => 'Halo, saya ingin bertanya tentang KPR.',
            'status' => 'FINAL',
            'started_at' => now()->subMinutes(5)->toIso8601String(),
            'ended_at' => now()->subMinutes(5)->addSeconds(5)->toIso8601String(),
        ], $overrides);
    }

    public function test_owner_can_submit_transcript_turn(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createActiveSession($user);

        $response = $this->actingAs($user)
            ->postJson(
                route('training.sessions.transcript.store', $session->public_id),
                $this->validPayload(),
            );

        $response->assertOk()
            ->assertJsonPath('turn.sequence', 0)
            ->assertJsonPath('turn.speaker', 'USER')
            ->assertJsonPath('turn.status', 'FINAL');

        $this->assertDatabaseHas('roleplay_transcript_turns', [
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'USER',
            'status' => 'FINAL',
        ]);
    }

    public function test_another_user_cannot_submit_transcript(): void
    {
        $owner = User::factory()->sales()->active()->create();
        $other = User::factory()->sales()->active()->create();
        $session = $this->createActiveSession($owner);

        $response = $this->actingAs($other)
            ->postJson(
                route('training.sessions.transcript.store', $session->public_id),
                $this->validPayload(),
            );

        $response->assertNotFound();
    }

    public function test_pending_user_cannot_submit_transcript(): void
    {
        $user = User::factory()->sales()->pendingApproval()->create();
        $session = $this->createActiveSession($user);

        $response = $this->actingAs($user)
            ->postJson(
                route('training.sessions.transcript.store', $session->public_id),
                $this->validPayload(),
            );

        $response->assertForbidden();
    }

    public function test_suspended_user_cannot_submit_transcript(): void
    {
        $user = User::factory()->sales()->suspended()->create();
        $session = $this->createActiveSession($user);

        $response = $this->actingAs($user)
            ->postJson(
                route('training.sessions.transcript.store', $session->public_id),
                $this->validPayload(),
            );

        $response->assertForbidden();
    }

    public function test_duplicate_final_turn_is_rejected(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createActiveSession($user);

        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'USER',
            'text' => 'Halo, saya ingin bertanya tentang KPR.',
            'status' => 'FINAL',
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                route('training.sessions.transcript.store', $session->public_id),
                $this->validPayload(sequence: 0, overrides: ['text' => 'Teks yang berbeda untuk turn yang sama.']),
            );

        $response->assertStatus(409)
            ->assertJsonPath('error', 'turn_already_final');

        $this->assertDatabaseHas('roleplay_transcript_turns', [
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'text' => 'Halo, saya ingin bertanya tentang KPR.',
        ]);
    }

    public function test_partial_turn_can_be_updated_to_final(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createActiveSession($user);

        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'USER',
            'text' => 'Halo,',
            'status' => 'PARTIAL',
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                route('training.sessions.transcript.store', $session->public_id),
                $this->validPayload(sequence: 0, overrides: [
                    'speaker' => 'USER',
                    'text' => 'Halo, saya ingin bertanya tentang KPR.',
                    'status' => 'FINAL',
                ]),
            );

        $response->assertOk()
            ->assertJsonPath('turn.status', 'FINAL');

        $this->assertDatabaseHas('roleplay_transcript_turns', [
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'text' => 'Halo, saya ingin bertanya tentang KPR.',
            'status' => 'FINAL',
        ]);

        $this->assertDatabaseCount('roleplay_transcript_turns', 1);
    }

    public function test_sequence_ordering_is_preserved(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createActiveSession($user);

        $this->actingAs($user)
            ->postJson(
                route('training.sessions.transcript.store', $session->public_id),
                $this->validPayload(sequence: 0, overrides: ['speaker' => 'AI', 'text' => 'Selamat siang.']),
            )->assertOk();

        $this->actingAs($user)
            ->postJson(
                route('training.sessions.transcript.store', $session->public_id),
                $this->validPayload(sequence: 1, overrides: ['speaker' => 'USER', 'text' => 'Saya ingin tanya KPR.']),
            )->assertOk();

        $this->actingAs($user)
            ->postJson(
                route('training.sessions.transcript.store', $session->public_id),
                $this->validPayload(sequence: 2, overrides: ['speaker' => 'AI', 'text' => 'Baik, silakan.']),
            )->assertOk();

        $turns = RoleplayTranscriptTurn::where('roleplay_session_id', $session->id)
            ->orderBy('sequence')
            ->get();

        $this->assertCount(3, $turns);
        $this->assertEquals(0, $turns[0]->sequence);
        $this->assertEquals(1, $turns[1]->sequence);
        $this->assertEquals(2, $turns[2]->sequence);
        $this->assertEquals('Selamat siang.', $turns[0]->text);
        $this->assertEquals('Saya ingin tanya KPR.', $turns[1]->text);
        $this->assertEquals('Baik, silakan.', $turns[2]->text);
    }

    public function test_final_transcript_turns_are_stored(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createActiveSession($user);

        $this->actingAs($user)
            ->postJson(
                route('training.sessions.transcript.store', $session->public_id),
                $this->validPayload(sequence: 0, overrides: [
                    'speaker' => 'AI',
                    'text' => 'Selamat siang, ada yang bisa saya bantu?',
                    'status' => 'FINAL',
                    'started_at' => '2026-07-08T10:00:00Z',
                    'ended_at' => '2026-07-08T10:00:08Z',
                ]),
            )->assertOk();

        $this->assertDatabaseHas('roleplay_transcript_turns', [
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'AI',
            'status' => 'FINAL',
        ]);

        $this->assertDatabaseCount('roleplay_transcript_turns', 1);
    }

    public function test_actor_instructions_are_never_exposed(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createActiveSession($user);

        $response = $this->actingAs($user)
            ->postJson(
                route('training.sessions.transcript.store', $session->public_id),
                $this->validPayload(),
            );

        $response->assertOk();
        $body = $response->getContent();

        $this->assertStringNotContainsString('actor_instructions', $body);
        $this->assertStringNotContainsString('actor_instruction_hash', $body);
        $this->assertStringNotContainsString('Instruksi internal rahasia', $body);
    }

    public function test_submit_with_invalid_session_status_is_rejected(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()
            ->forUser($user)
            ->status(RoleplaySessionStatus::CREATED)
            ->create();

        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                route('training.sessions.transcript.store', $session->public_id),
                $this->validPayload(),
            );

        $response->assertStatus(409)
            ->assertJsonPath('error', 'invalid_session_status');
    }

    public function test_partial_turn_can_be_updated_with_newer_partial(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->createActiveSession($user);

        $this->actingAs($user)
            ->postJson(
                route('training.sessions.transcript.store', $session->public_id),
                $this->validPayload(sequence: 0, overrides: [
                    'text' => 'Halo,',
                    'status' => 'PARTIAL',
                ]),
            )->assertOk()
            ->assertJsonPath('turn.status', 'PARTIAL');

        $this->actingAs($user)
            ->postJson(
                route('training.sessions.transcript.store', $session->public_id),
                $this->validPayload(sequence: 0, overrides: [
                    'text' => 'Halo, saya ingin bertanya.',
                    'status' => 'PARTIAL',
                ]),
            )->assertOk()
            ->assertJsonPath('turn.status', 'PARTIAL');

        $this->assertDatabaseHas('roleplay_transcript_turns', [
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'text' => 'Halo, saya ingin bertanya.',
            'status' => 'PARTIAL',
        ]);

        $this->assertDatabaseCount('roleplay_transcript_turns', 1);
    }
}
