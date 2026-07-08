<?php

namespace Tests\Feature;

use App\Enums\RoleplaySessionStatus;
use App\Models\DirectorNote;
use App\Models\RoleplayEvent;
use App\Models\RoleplaySession;
use App\Models\RoleplaySessionSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleplayDirectorEventTest extends TestCase
{
    use RefreshDatabase;

    private function activeSession(User $user): RoleplaySession
    {
        $session = RoleplaySession::factory()
            ->forUser($user)
            ->active()
            ->create();

        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
        ]);

        return $session;
    }

    private function eventUrl(RoleplaySession $session): string
    {
        return route('training.sessions.director.events.store', $session->public_id);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'event_type' => 'ACTIVE_LISTENING',
        ], $overrides);
    }

    public function test_owner_can_submit_event(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->activeSession($user);

        $response = $this->actingAs($user)
            ->postJson($this->eventUrl($session), $this->validPayload());

        $response->assertOk()
            ->assertJsonPath('accepted', true)
            ->assertJsonPath('event.event_type', 'ACTIVE_LISTENING')
            ->assertJsonStructure([
                'accepted', 'state', 'applied_transition', 'notes', 'event' => ['id', 'event_type', 'fingerprint'],
            ]);

        $this->assertDatabaseHas('roleplay_events', [
            'roleplay_session_id' => $session->id,
            'event_type' => 'ACTIVE_LISTENING',
            'accepted' => true,
        ]);
    }

    public function test_rejects_invalid_event_type(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->activeSession($user);

        $response = $this->actingAs($user)
            ->postJson($this->eventUrl($session), [
                'event_type' => 'INVALID_EVENT',
            ]);

        $response->assertStatus(422);
    }

    public function test_rejects_non_active_session(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()
            ->forUser($user)
            ->create(['status' => RoleplaySessionStatus::CREATED->value]);

        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson($this->eventUrl($session), $this->validPayload());

        $response->assertStatus(409)
            ->assertJsonPath('error', 'invalid_session_status');
    }

    public function test_rejects_non_owner(): void
    {
        $owner = User::factory()->sales()->active()->create();
        $other = User::factory()->sales()->active()->create();
        $session = $this->activeSession($owner);

        $response = $this->actingAs($other)
            ->postJson($this->eventUrl($session), $this->validPayload());

        $response->assertNotFound();
    }

    public function test_pending_user_cannot_submit(): void
    {
        $user = User::factory()->sales()->pendingApproval()->create();
        $session = $this->activeSession($user);

        $response = $this->actingAs($user)
            ->postJson($this->eventUrl($session), $this->validPayload());

        $response->assertForbidden();
    }

    public function test_suspended_user_cannot_submit(): void
    {
        $user = User::factory()->sales()->suspended()->create();
        $session = $this->activeSession($user);

        $response = $this->actingAs($user)
            ->postJson($this->eventUrl($session), $this->validPayload());

        $response->assertForbidden();
    }

    public function test_duplicate_event_is_idempotent(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->activeSession($user);

        $payload = $this->validPayload(['event_type' => 'ACTIVE_LISTENING']);

        $first = $this->actingAs($user)
            ->postJson($this->eventUrl($session), $payload);

        $first->assertOk()->assertJsonPath('accepted', true);

        $second = $this->actingAs($user)
            ->postJson($this->eventUrl($session), $payload);

        $second->assertOk()
            ->assertJsonPath('accepted', true)
            ->assertJsonPath('event.id', $first->json('event.id'));

        $this->assertDatabaseCount('roleplay_events', 1);
    }

    public function test_accepts_optional_fields(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->activeSession($user);

        $payload = $this->validPayload([
            'event_type' => 'OBJECTION_TRIGGERED',
            'severity' => 'HIGH',
            'topic' => 'INSTALLMENT',
            'related_objection_key' => 'expensive_installment',
            'hidden_information_key' => 'past_slik_issue',
            'short_internal_reason' => 'Customer objected to price.',
            'source_turn_sequence' => 5,
        ]);

        $response = $this->actingAs($user)
            ->postJson($this->eventUrl($session), $payload);

        $response->assertOk()
            ->assertJsonPath('accepted', true);

        $this->assertDatabaseHas('roleplay_events', [
            'roleplay_session_id' => $session->id,
            'event_type' => 'OBJECTION_TRIGGERED',
            'severity' => 'HIGH',
            'topic' => 'INSTALLMENT',
            'related_objection_key' => 'expensive_installment',
            'hidden_information_key' => 'past_slik_issue',
            'short_internal_reason' => 'Customer objected to price.',
            'source_turn_sequence' => 5,
        ]);
    }

    public function test_objection_transition_generates_note(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()
            ->forUser($user)
            ->active()
            ->create();

        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
            'persona_snapshot_json' => [
                'persona_key' => 'test-persona',
                'name' => 'Test Persona',
                'version_number' => 1,
                'identity' => [],
                'housing_context' => [],
                'knowledge_beliefs' => [],
                'misconceptions' => [],
                'personality_profile' => [],
                'human_behavior_traits' => [],
                'communication_style' => [],
                'initial_dynamic_state' => [],
                'state_sensitivity' => [],
                'objections' => [
                    ['key' => 'expensive_installment', 'visibility' => 'VISIBLE', 'persistence' => 50, 'title' => 'Installment too expensive'],
                ],
                'hidden_information' => [],
                'salience_overrides' => [],
            ],
        ]);

        $response = $this->actingAs($user)
            ->postJson($this->eventUrl($session), [
                'event_type' => 'OBJECTION_TRIGGERED',
                'related_objection_key' => 'expensive_installment',
            ]);

        $response->assertOk()
            ->assertJsonPath('accepted', true);

        $notes = $response->json('notes');
        $this->assertNotEmpty($notes);

        $objectionNotes = array_filter($notes, fn(array $n) => $n['category'] === 'objection');
        $this->assertNotEmpty($objectionNotes);

        $this->assertDatabaseHas('director_notes', [
            'roleplay_session_id' => $session->id,
            'category' => 'objection',
        ]);
    }

    public function test_boundary_transition_generates_note(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->activeSession($user);

        $response = $this->actingAs($user)
            ->postJson($this->eventUrl($session), [
                'event_type' => 'CUSTOMER_BOUNDARY_TEST',
            ]);

        $response->assertOk()
            ->assertJsonPath('accepted', true);

        $notes = $response->json('notes');
        $this->assertNotEmpty($notes);

        $boundaryNotes = array_filter($notes, fn(array $n) => $n['category'] === 'boundary');
        $this->assertNotEmpty($boundaryNotes);

        $this->assertDatabaseHas('director_notes', [
            'roleplay_session_id' => $session->id,
            'category' => 'boundary',
        ]);
    }

    public function test_phase_transition_generates_note(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->activeSession($user);

        $response = $this->actingAs($user)
            ->postJson($this->eventUrl($session), [
                'event_type' => 'GOOD_OPENING',
            ]);

        $response->assertOk()
            ->assertJsonPath('accepted', true);

        $notes = $response->json('notes');
        $this->assertNotEmpty($notes);

        $phaseNotes = array_filter($notes, fn(array $n) => $n['category'] === 'phase_change');
        $this->assertNotEmpty($phaseNotes);
    }

    public function test_state_persists_across_events(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = $this->activeSession($user);

        $first = $this->actingAs($user)
            ->postJson($this->eventUrl($session), [
                'event_type' => 'GOOD_OPENING',
            ]);

        $first->assertOk();
        $firstState = $first->json('state');

        $second = $this->actingAs($user)
            ->postJson($this->eventUrl($session), [
                'event_type' => 'ACTIVE_LISTENING',
            ]);

        $second->assertOk();
        $secondState = $second->json('state');

        $this->assertNotEquals(
            $firstState['trust'],
            $secondState['trust'],
            'Trust should have changed after two different events (different fingerprints)',
        );
    }
}
