<?php

namespace Tests\Unit;

use App\Enums\EndingType;
use App\Enums\EvaluationStatus;
use App\Enums\PersonaMode;
use App\Enums\RoleplaySessionStatus;
use App\Enums\TranscriptIntegrity;
use App\Models\RoleplaySession;
use App\Models\RoleplaySessionSnapshot;
use App\Models\User;
use App\Services\Snapshots\DifficultySnapshot;
use App\Services\Snapshots\DirectorSnapshot;
use App\Services\Snapshots\PersonaSnapshot;
use App\Services\Snapshots\RubricSnapshot;
use App\Services\Snapshots\SalienceSnapshot;
use App\Services\Snapshots\ScenarioSnapshot;
use App\Services\Snapshots\SessionSnapshotService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use LogicException;
use Tests\TestCase;

class RoleplaySessionTest extends TestCase
{
    use LazilyRefreshDatabase;
    public function test_session_can_be_created_for_active_sales_user(): void
    {
        $user = User::factory()->sales()->active()->create();

        $session = RoleplaySession::factory()->forUser($user)->create();

        $this->assertNotNull($session->id);
        $this->assertSame($user->id, $session->user_id);
        $this->assertSame(RoleplaySessionStatus::CREATED->value, $session->status);
        $this->assertNotNull($session->public_id);
        $this->assertNotNull($session->correlation_id);
    }

    public function test_session_belongs_to_user(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($user)->create();

        $this->assertTrue($session->user()->exists());
        $this->assertSame($user->id, $session->user->id);
    }

    public function test_session_has_snapshot(): void
    {
        $session = RoleplaySession::factory()->create();
        $snapshot = RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
        ]);

        $loaded = $session->snapshot;
        $this->assertNotNull($loaded);
        $this->assertSame($snapshot->id, $loaded->id);
        $this->assertSame($session->id, $loaded->roleplay_session_id);
    }

    public function test_actor_instruction_hash_is_stored(): void
    {
        $snapshot = RoleplaySessionSnapshot::factory()->create();

        $this->assertNotNull($snapshot->actor_instruction_hash);
        $this->assertSame(64, strlen($snapshot->actor_instruction_hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $snapshot->actor_instruction_hash);
    }

    public function test_actor_instructions_are_encrypted_in_database(): void
    {
        $snapshot = RoleplaySessionSnapshot::factory()->create();

        $raw = \DB::table('roleplay_session_snapshots')
            ->where('id', $snapshot->id)
            ->value('actor_instructions');

        $this->assertNotNull($raw);
        $this->assertNotEquals('=== AKTOR PERSONA ===', $raw);
        $this->assertStringStartsWith('eyJ', $raw);
    }

    public function test_actor_instructions_decrypt_when_accessed(): void
    {
        $originalText = '=== AKTOR PERSONA ===' . "\nTest persona instructions for decryption test.";
        $snapshot = RoleplaySessionSnapshot::factory()->create([
            'actor_instructions' => $originalText,
        ]);

        $this->assertSame($originalText, $snapshot->actor_instructions);
    }

    public function test_snapshot_json_is_stored(): void
    {
        $session = RoleplaySession::factory()->create();
        $snapshot = RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
        ]);

        $this->assertIsArray($snapshot->persona_snapshot_json);
        $this->assertSame('test-persona', $snapshot->persona_snapshot_json['persona_key']);

        $this->assertIsArray($snapshot->scenario_snapshot_json);
        $this->assertSame('test-scenario', $snapshot->scenario_snapshot_json['scenario_key']);

        $this->assertIsArray($snapshot->difficulty_snapshot_json);
        $this->assertSame('NORMAL', $snapshot->difficulty_snapshot_json['level']);

        $this->assertIsArray($snapshot->salience_snapshot_json);

        $this->assertIsArray($snapshot->rubric_snapshot_json);
        $this->assertArrayHasKey('items', $snapshot->rubric_snapshot_json);

        $this->assertIsArray($snapshot->director_snapshot_json);
        $this->assertArrayHasKey('initial_state', $snapshot->director_snapshot_json);
    }

    public function test_snapshot_can_be_created(): void
    {
        $snapshot = RoleplaySessionSnapshot::factory()->create();

        $this->assertNotNull($snapshot->id);
        $this->assertNotNull($snapshot->roleplay_session_id);
        $this->assertTrue($snapshot->exists);
    }

    public function test_updating_snapshot_after_creation_is_blocked(): void
    {
        $snapshot = RoleplaySessionSnapshot::factory()->create();
        $originalScenarioSnapshot = $snapshot->scenario_snapshot_json;

        try {
            $snapshot->update([
                'scenario_snapshot_json' => array_merge($originalScenarioSnapshot, ['name' => 'Changed']),
            ]);
            $this->fail('Snapshot update should have been rejected.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        $this->assertSame($originalScenarioSnapshot, $snapshot->fresh()->scenario_snapshot_json);
    }

    public function test_actor_instructions_cannot_be_changed_after_creation(): void
    {
        $snapshot = RoleplaySessionSnapshot::factory()->create([
            'actor_instructions' => 'Original actor instructions.',
        ]);

        try {
            $snapshot->update(['actor_instructions' => 'Changed actor instructions.']);
            $this->fail('Actor instructions update should have been rejected.');
        } catch (LogicException) {
            // Expected.
        }

        $this->assertSame('Original actor instructions.', $snapshot->fresh()->actor_instructions);
    }

    public function test_actor_instruction_hash_cannot_be_changed_after_creation(): void
    {
        $snapshot = RoleplaySessionSnapshot::factory()->create();
        $originalHash = $snapshot->actor_instruction_hash;

        try {
            $snapshot->update(['actor_instruction_hash' => str_repeat('a', 64)]);
            $this->fail('Actor instruction hash update should have been rejected.');
        } catch (LogicException) {
            // Expected.
        }

        $this->assertSame($originalHash, $snapshot->fresh()->actor_instruction_hash);
    }

    public function test_json_snapshot_fields_cannot_be_changed_after_creation(): void
    {
        $fields = [
            'persona_snapshot_json',
            'scenario_snapshot_json',
            'difficulty_snapshot_json',
            'salience_snapshot_json',
            'rubric_snapshot_json',
            'director_snapshot_json',
        ];

        foreach ($fields as $field) {
            $snapshot = RoleplaySessionSnapshot::factory()->create();
            $original = $snapshot->{$field};

            try {
                $snapshot->update([$field => array_merge($original, ['changed' => true])]);
                $this->fail("$field update should have been rejected.");
            } catch (LogicException) {
                // Expected.
            }

            $this->assertSame($original, $snapshot->fresh()->{$field});
        }
    }

    public function test_session_can_transition_through_lifecycle(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($user)->create();

        $this->assertSame(RoleplaySessionStatus::CREATED->value, $session->status);

        $session->update(['status' => RoleplaySessionStatus::PREPARING->value]);
        $session->refresh();
        $this->assertSame(RoleplaySessionStatus::PREPARING->value, $session->status);

        $session->update(['status' => RoleplaySessionStatus::ACTIVE->value]);
        $session->refresh();
        $this->assertTrue($session->isActive());
        $this->assertTrue($session->canReceiveEvents());

        $session->markEnded(EndingType::USER_END->value, 'User ended the session');
        $session->refresh();
        $this->assertSame(RoleplaySessionStatus::ENDING->value, $session->status);
        $this->assertSame(EndingType::USER_END->value, $session->ending_type);
        $this->assertNotNull($session->ended_at);

        $session->update(['status' => RoleplaySessionStatus::COMPLETED->value]);
        $session->refresh();
        $this->assertFalse($session->canEnd());
    }

    public function test_session_status_checks(): void
    {
        $active = RoleplaySession::factory()->active()->create();
        $this->assertTrue($active->isActive());
        $this->assertTrue($active->canReceiveEvents());
        $this->assertTrue($active->canEnd());

        $created = RoleplaySession::factory()->create();
        $this->assertFalse($created->isActive());
        $this->assertFalse($created->canReceiveEvents());
        $this->assertTrue($created->canEnd());

        $completed = RoleplaySession::factory()->completed()->create();
        $this->assertFalse($completed->isActive());
        $this->assertFalse($completed->canReceiveEvents());
        $this->assertFalse($completed->canEnd());
    }

    public function test_session_scopes(): void
    {
        $user1 = User::factory()->sales()->active()->create();
        $user2 = User::factory()->sales()->active()->create();

        $s1 = RoleplaySession::factory()->forUser($user1)->active()->create();
        $s2 = RoleplaySession::factory()->forUser($user2)->create();

        $user1Sessions = RoleplaySession::forUser($user1)->get();
        $this->assertCount(1, $user1Sessions);
        $this->assertSame($s1->id, $user1Sessions->first()->id);

        $activeSessions = RoleplaySession::active()->get();
        $this->assertCount(1, $activeSessions);
        $this->assertSame($s1->id, $activeSessions->first()->id);
    }

    public function test_public_id_is_generated(): void
    {
        $id1 = RoleplaySession::generatePublicId();
        $id2 = RoleplaySession::generatePublicId();

        $this->assertSame(12, strlen($id1));
        $this->assertSame(12, strlen($id2));
        $this->assertNotSame($id1, $id2);
        $this->assertMatchesRegularExpression('/^[a-z0-9]{12}$/', $id1);
    }

    public function test_snapshot_is_one_per_session(): void
    {
        $session = RoleplaySession::factory()->create();

        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
        ]);
    }

    public function test_session_to_array_does_not_expose_snapshot_data(): void
    {
        $session = RoleplaySession::factory()->create();
        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
        ]);

        $data = $session->toArray();

        $this->assertArrayHasKey('public_id', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayNotHasKey('persona_snapshot_json', $data);
        $this->assertArrayNotHasKey('scenario_snapshot_json', $data);
        $this->assertArrayNotHasKey('director_snapshot_json', $data);
        $this->assertArrayNotHasKey('actor_instructions', $data);
        $this->assertArrayNotHasKey('actor_instruction_hash', $data);
    }
}
