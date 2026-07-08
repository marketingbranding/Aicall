<?php

namespace Tests\Feature;

use App\Enums\RoleplaySessionStatus;
use App\Models\RoleplaySession;
use App\Models\RoleplaySessionSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoleplayLiveCredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_active_sales_user_can_request_token(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['name' => 'authTokens/test-token'], 200),
        ]);
        config(['gemini.api_key' => 'server-secret-key']);
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSessionWithSnapshot($user);

        $response = $this->actingAs($user)
            ->postJson(route('training.sessions.live-credentials.store', $session->public_id));

        $response->assertOk()
            ->assertJsonPath('provider', 'gemini')
            ->assertJsonPath('model', 'gemini-3.1-flash-live-preview')
            ->assertJsonPath('ephemeral_token', 'authTokens/test-token')
            ->assertJsonPath('session.public_id', $session->public_id)
            ->assertJsonPath('live_config.response_modalities.0', 'AUDIO')
            ->assertJsonPath('live_config.input_transcription', true)
            ->assertJsonPath('live_config.output_transcription', true)
            ->assertJsonPath('live_config.session_resumption', true);

        Http::assertSent(fn ($request) =>
            str_contains($request->url(), 'v1alpha/auth_tokens')
            && data_get($request->data(), 'authToken.uses') === 1
            && data_get($request->data(), 'authToken.liveConnectConstraints.model') === 'gemini-3.1-flash-live-preview'
            && data_get($request->data(), 'authToken.liveConnectConstraints.config.systemInstruction.parts.0.text') === 'Instruksi internal rahasia.'
        );
    }

    public function test_another_sales_user_cannot_request_token(): void
    {
        Http::fake();
        config(['gemini.api_key' => 'server-secret-key']);
        $owner = User::factory()->sales()->active()->create();
        $otherUser = User::factory()->sales()->active()->create();
        $session = $this->createSessionWithSnapshot($owner);

        $this->actingAs($otherUser)
            ->postJson(route('training.sessions.live-credentials.store', $session->public_id))
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_pending_and_suspended_users_cannot_request_token(): void
    {
        Http::fake();
        config(['gemini.api_key' => 'server-secret-key']);
        $pending = User::factory()->sales()->pendingApproval()->create();
        $suspended = User::factory()->sales()->suspended()->create();
        $pendingSession = $this->createSessionWithSnapshot($pending);
        $suspendedSession = $this->createSessionWithSnapshot($suspended);

        $this->actingAs($pending)
            ->postJson(route('training.sessions.live-credentials.store', $pendingSession->public_id))
            ->assertForbidden();

        $this->actingAs($suspended)
            ->postJson(route('training.sessions.live-credentials.store', $suspendedSession->public_id))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_invalid_session_status_cannot_request_token(): void
    {
        Http::fake();
        config(['gemini.api_key' => 'server-secret-key']);
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSessionWithSnapshot($user, RoleplaySessionStatus::ENDING);

        $this->actingAs($user)
            ->postJson(route('training.sessions.live-credentials.store', $session->public_id))
            ->assertStatus(409);

        Http::assertNothingSent();
    }

    public function test_ready_session_can_request_token(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['name' => 'authTokens/test-token'], 200),
        ]);
        config(['gemini.api_key' => 'server-secret-key']);
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSessionWithSnapshot($user, RoleplaySessionStatus::READY);

        $this->actingAs($user)
            ->postJson(route('training.sessions.live-credentials.store', $session->public_id))
            ->assertOk()
            ->assertJsonPath('ephemeral_token', 'authTokens/test-token');
    }

    public function test_active_session_can_request_token(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['name' => 'authTokens/test-token'], 200),
        ]);
        config(['gemini.api_key' => 'server-secret-key']);
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSessionWithSnapshot($user, RoleplaySessionStatus::ACTIVE);

        $this->actingAs($user)
            ->postJson(route('training.sessions.live-credentials.store', $session->public_id))
            ->assertOk()
            ->assertJsonPath('ephemeral_token', 'authTokens/test-token');
    }

    public function test_response_does_not_expose_permanent_api_key_or_actor_instructions(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['name' => 'authTokens/test-token'], 200),
        ]);
        config(['gemini.api_key' => 'server-secret-key']);
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSessionWithSnapshot($user);

        $response = $this->actingAs($user)
            ->postJson(route('training.sessions.live-credentials.store', $session->public_id));

        $response->assertOk();
        $body = $response->getContent();

        $this->assertStringNotContainsString('server-secret-key', $body);
        $this->assertStringNotContainsString('Instruksi internal rahasia', $body);
        $this->assertStringNotContainsString('actor_instructions', $body);
        $this->assertStringNotContainsString('hidden_information', $body);
        $this->assertStringNotContainsString('director_snapshot', $body);
    }

    public function test_response_includes_first_speaker_default(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['name' => 'authTokens/test-token'], 200),
        ]);
        config(['gemini.api_key' => 'server-secret-key']);
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSessionWithSnapshot($user);

        $response = $this->actingAs($user)
            ->postJson(route('training.sessions.live-credentials.store', $session->public_id));

        $response->assertOk()
            ->assertJsonPath('first_speaker', 'USER');
    }

    public function test_response_includes_first_speaker_when_ai(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['name' => 'authTokens/test-token'], 200),
        ]);
        config(['gemini.api_key' => 'server-secret-key']);
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSessionWithSnapshot($user, scenarioSnapshotOverrides: [
            'first_speaker' => 'AI',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('training.sessions.live-credentials.store', $session->public_id));

        $response->assertOk()
            ->assertJsonPath('first_speaker', 'AI');
    }

    public function test_missing_api_key_returns_controlled_error(): void
    {
        Http::fake();
        config(['gemini.api_key' => null]);
        $user = User::factory()->sales()->active()->create();
        $session = $this->createSessionWithSnapshot($user);

        $this->actingAs($user)
            ->postJson(route('training.sessions.live-credentials.store', $session->public_id))
            ->assertStatus(503)
            ->assertJsonPath('error', 'live_credentials_unavailable');

        Http::assertNothingSent();
    }

    private function createSessionWithSnapshot(
        User $user,
        RoleplaySessionStatus $status = RoleplaySessionStatus::CREATED,
        array $scenarioSnapshotOverrides = [],
    ): RoleplaySession {
        $session = RoleplaySession::factory()
            ->forUser($user)
            ->status($status)
            ->create();

        $defaultScenario = RoleplaySessionSnapshot::factory()->definition()['scenario_snapshot_json'];

        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
            'actor_instructions' => 'Instruksi internal rahasia.',
            'persona_snapshot_json' => array_merge(
                RoleplaySessionSnapshot::factory()->definition()['persona_snapshot_json'],
                ['hidden_information' => [['information' => 'Rahasia keluarga']]],
            ),
            'scenario_snapshot_json' => array_merge($defaultScenario, $scenarioSnapshotOverrides),
        ]);

        return $session;
    }
}
