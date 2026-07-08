<?php

namespace Tests\Unit\Services\Transcript;

use App\Enums\TranscriptIntegrity;
use App\Models\RoleplaySession;
use App\Models\RoleplaySessionSnapshot;
use App\Models\RoleplayTranscriptTurn;
use App\Models\User;
use App\Services\Transcript\TranscriptAssembler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranscriptAssemblerTest extends TestCase
{
    use RefreshDatabase;

    private function createSession(): RoleplaySession
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()
            ->forUser($user)
            ->completed()
            ->create();

        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
        ]);

        return $session;
    }

    public function test_ordered_final_transcript_assembly(): void
    {
        $session = $this->createSession();

        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'AI',
            'text' => 'Selamat siang, ada yang bisa saya bantu?',
            'status' => 'FINAL',
        ]);
        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 1,
            'speaker' => 'USER',
            'text' => 'Saya ingin bertanya tentang KPR.',
            'status' => 'FINAL',
        ]);
        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 2,
            'speaker' => 'AI',
            'text' => 'Baik, silakan. Ada yang ingin ditanyakan?',
            'status' => 'FINAL',
        ]);

        $assembler = app(TranscriptAssembler::class);
        $result = $assembler->assemble($session);

        $this->assertTrue($result->isComplete());
        $this->assertEquals(TranscriptIntegrity::COMPLETE, $result->integrity);
        $this->assertCount(3, $result->turns);
        $this->assertEmpty($result->issues);
        $this->assertEmpty($result->interruptedTurns);
        $this->assertEquals('Selamat siang, ada yang bisa saya bantu?', $result->turns[0]->text);
        $this->assertEquals('Saya ingin bertanya tentang KPR.', $result->turns[1]->text);
    }

    public function test_duplicate_update_behavior_remains_safe(): void
    {
        $session = $this->createSession();

        $turn = RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'USER',
            'text' => 'Halo,',
            'status' => 'PARTIAL',
        ]);

        $turn->update([
            'text' => 'Halo, saya ingin bertanya tentang KPR.',
            'status' => 'FINAL',
        ]);

        $assembler = app(TranscriptAssembler::class);
        $result = $assembler->assemble($session);

        $this->assertTrue($result->isComplete());
        $this->assertEquals(TranscriptIntegrity::COMPLETE, $result->integrity);
        $this->assertCount(1, $result->turns);
        $this->assertEmpty($result->issues);
        $this->assertEquals('Halo, saya ingin bertanya tentang KPR.', $result->turns[0]->text);
    }

    public function test_missing_sequence_detected(): void
    {
        $session = $this->createSession();

        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'USER',
            'text' => 'Halo.',
            'status' => 'FINAL',
        ]);
        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 2,
            'speaker' => 'AI',
            'text' => 'Halo juga.',
            'status' => 'FINAL',
        ]);

        $assembler = app(TranscriptAssembler::class);
        $result = $assembler->assemble($session);

        $this->assertEquals(TranscriptIntegrity::PARTIAL, $result->integrity);
        $this->assertFalse($result->isComplete());
        $this->assertCount(2, $result->turns);
        $this->assertNotEmpty($result->issues);
        $this->assertStringContainsString('sekuen 1 hilang', $result->issues[0]);
    }

    public function test_empty_text_detected(): void
    {
        $session = $this->createSession();

        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'USER',
            'text' => 'Halo.',
            'status' => 'FINAL',
        ]);
        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 1,
            'speaker' => 'AI',
            'text' => '',
            'status' => 'FINAL',
        ]);

        $assembler = app(TranscriptAssembler::class);
        $result = $assembler->assemble($session);

        $this->assertEquals(TranscriptIntegrity::PARTIAL, $result->integrity);
        $this->assertFalse($result->isComplete());
        $this->assertCount(2, $result->turns);
        $this->assertNotEmpty($result->issues);
        $this->assertStringContainsString('teks kosong', $result->issues[0]);
    }

    public function test_partial_only_transcript_becomes_partial(): void
    {
        $session = $this->createSession();

        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'AI',
            'text' => 'Selamat siang,',
            'status' => 'PARTIAL',
        ]);
        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 1,
            'speaker' => 'USER',
            'text' => 'Halo,',
            'status' => 'PARTIAL',
        ]);

        $assembler = app(TranscriptAssembler::class);
        $result = $assembler->assemble($session);

        $this->assertEquals(TranscriptIntegrity::PARTIAL, $result->integrity);
        $this->assertFalse($result->isComplete());
        $this->assertCount(2, $result->turns);
        $this->assertEmpty($result->issues);
    }

    public function test_clean_final_transcript_becomes_complete(): void
    {
        $session = $this->createSession();

        for ($i = 0; $i < 5; $i++) {
            RoleplayTranscriptTurn::factory()->create([
                'roleplay_session_id' => $session->id,
                'sequence' => $i,
                'speaker' => $i % 2 === 0 ? 'AI' : 'USER',
                'text' => "Teks percakapan sekuen $i.",
                'status' => 'FINAL',
            ]);
        }

        $assembler = app(TranscriptAssembler::class);
        $result = $assembler->assemble($session);

        $this->assertTrue($result->isComplete());
        $this->assertEquals(TranscriptIntegrity::COMPLETE, $result->integrity);
        $this->assertCount(5, $result->turns);
        $this->assertEmpty($result->issues);
        $this->assertEmpty($result->interruptedTurns);
    }

    public function test_no_turns_becomes_failed(): void
    {
        $session = $this->createSession();

        $assembler = app(TranscriptAssembler::class);
        $result = $assembler->assemble($session);

        $this->assertEquals(TranscriptIntegrity::FAILED, $result->integrity);
        $this->assertFalse($result->isComplete());
        $this->assertEmpty($result->turns);
        $this->assertNotEmpty($result->issues);
    }

    public function test_interrupted_ai_turn_detected(): void
    {
        $session = $this->createSession();

        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'AI',
            'text' => 'Selamat siang, ada yang bisa saya bantu?',
            'status' => 'FINAL',
        ]);
        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 1,
            'speaker' => 'USER',
            'text' => 'Saya ingin tanya KPR.',
            'status' => 'FINAL',
        ]);
        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 2,
            'speaker' => 'AI',
            'text' => 'Tentu, silakan..',
            'status' => 'PARTIAL',
        ]);

        $assembler = app(TranscriptAssembler::class);
        $result = $assembler->assemble($session);

        $this->assertEquals(TranscriptIntegrity::PARTIAL, $result->integrity);
        $this->assertFalse($result->isComplete());
        $this->assertCount(3, $result->turns);
        $this->assertCount(1, $result->interruptedTurns);
        $this->assertEquals(2, $result->interruptedTurns[0]['sequence']);
        $this->assertEquals('AI', $result->interruptedTurns[0]['speaker']);
        $this->assertStringContainsString('Tentu, silakan.', $result->interruptedTurns[0]['text']);
    }

    public function test_session_transcript_integrity_is_updated(): void
    {
        $session = $this->createSession();

        $this->assertNull($session->transcript_integrity);

        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'USER',
            'text' => 'Halo.',
            'status' => 'FINAL',
        ]);

        $assembler = app(TranscriptAssembler::class);
        $assembler->assemble($session);

        $session->refresh();
        $this->assertEquals(TranscriptIntegrity::COMPLETE->value, $session->transcript_integrity);
    }

    public function test_mixed_partial_and_final_transcript(): void
    {
        $session = $this->createSession();

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
            'status' => 'PARTIAL',
        ]);

        $assembler = app(TranscriptAssembler::class);
        $result = $assembler->assemble($session);

        $this->assertEquals(TranscriptIntegrity::PARTIAL, $result->integrity);
        $this->assertEmpty($result->interruptedTurns);
        $this->assertCount(2, $result->turns);
    }

    public function test_existing_tests_still_pass(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()
            ->forUser($user)
            ->active()
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

        $assembler = app(TranscriptAssembler::class);
        $result = $assembler->assemble($session);

        $this->assertTrue($result->isComplete());

        $this->actingAs($user)
            ->postJson(
                route('training.sessions.transcript.store', $session->public_id),
                [
                    'sequence' => 1,
                    'speaker' => 'AI',
                    'text' => 'Halo juga.',
                    'status' => 'FINAL',
                    'started_at' => now()->toIso8601String(),
                    'ended_at' => now()->addSeconds(5)->toIso8601String(),
                ],
            )->assertOk();

        $this->assertDatabaseHas('roleplay_transcript_turns', [
            'roleplay_session_id' => $session->id,
            'sequence' => 1,
            'text' => 'Halo juga.',
            'status' => 'FINAL',
        ]);
    }

    public function test_multiple_gaps_detected(): void
    {
        $session = $this->createSession();

        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 0,
            'speaker' => 'USER',
            'text' => 'Halo.',
            'status' => 'FINAL',
        ]);
        RoleplayTranscriptTurn::factory()->create([
            'roleplay_session_id' => $session->id,
            'sequence' => 3,
            'speaker' => 'AI',
            'text' => 'Halo juga.',
            'status' => 'FINAL',
        ]);

        $assembler = app(TranscriptAssembler::class);
        $result = $assembler->assemble($session);

        $this->assertEquals(TranscriptIntegrity::PARTIAL, $result->integrity);
        $this->assertCount(2, $result->issues);
        $this->assertStringContainsString('sekuen 1 hilang', $result->issues[0]);
        $this->assertStringContainsString('sekuen 2 hilang', $result->issues[1]);
    }
}
