<?php

namespace Tests\Feature;

use App\Enums\PersonaMode;
use App\Models\Persona;
use App\Models\RoleplaySession;
use App\Models\RoleplaySessionSnapshot;
use App\Models\Scenario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrainingBriefingTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_sales_can_view_briefing(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion()->create();

        $response = $this->actingAs($user)->get(route('training.scenarios.briefing', $scenario));

        $response->assertOk();
        $response->assertViewIs('training.briefing');
        $response->assertSee($scenario->name);
    }

    public function test_pending_user_cannot_view_briefing(): void
    {
        $user = User::factory()->sales()->pendingApproval()->create();
        $scenario = Scenario::factory()->withVersion()->create();

        $response = $this->actingAs($user)->get(route('training.scenarios.briefing', $scenario));

        $response->assertRedirect(route('account.pending', absolute: false));
    }

    public function test_suspended_user_cannot_view_briefing(): void
    {
        $user = User::factory()->sales()->suspended()->create();
        $scenario = Scenario::factory()->withVersion()->create();

        $response = $this->actingAs($user)->get(route('training.scenarios.briefing', $scenario));

        $response->assertRedirect(route('account.suspended', absolute: false));
    }

    public function test_archived_scenario_returns_404(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->archived()->withVersion()->create();

        $response = $this->actingAs($user)->get(route('training.scenarios.briefing', $scenario));

        $response->assertNotFound();
    }

    public function test_briefing_shows_scenario_info(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'description' => 'Latihan penanganan keberatan',
            'difficulty_level' => 'DIFFICULT',
            'max_duration_seconds' => 600,
            'sales_briefing' => 'Customer menolak cicilan.',
        ])->create();

        $response = $this->actingAs($user)->get(route('training.scenarios.briefing', $scenario));

        $response->assertOk();
        $response->assertSee('DIFFICULT');
        $response->assertSee('10:00');
        $response->assertSee('Latihan penanganan keberatan');
        $response->assertSee('Customer menolak cicilan.');
    }

    public function test_briefing_does_not_expose_hidden_scenario_fields(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'hidden_context' => 'rahasia skenario',
            'target_behaviors_json' => ['target_x'],
            'success_conditions_json' => ['sukses_a'],
            'failure_conditions_json' => ['gagal_b'],
            'prohibited_claims_json' => ['klaim_terlarang'],
        ])->create();

        $response = $this->actingAs($user)->get(route('training.scenarios.briefing', $scenario));

        $response->assertOk();
        $response->assertDontSee('rahasia skenario');
        $response->assertDontSee('target_x');
        $response->assertDontSee('sukses_a');
        $response->assertDontSee('gagal_b');
        $response->assertDontSee('klaim_terlarang');
    }

    public function test_briefing_shows_allowed_persona_modes(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => ['CHOOSE_PERSONA', 'RANDOM_PERSONA'],
        ])->create();

        $response = $this->actingAs($user)->get(route('training.scenarios.briefing', $scenario));

        $response->assertOk();
        $response->assertSee('Pilih Persona');
        $response->assertSee('Persona Acak');
        $response->assertDontSee('Persona Tersembunyi');
    }

    public function test_briefing_shows_choose_persona_list(): void
    {
        $user = User::factory()->sales()->active()->create();

        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => ['CHOOSE_PERSONA'],
        ])->create();

        $persona = Persona::factory()->create(['status' => Persona::STATUS_ACTIVE]);
        $version = $persona->versions()->create([
            'version_number' => 1,
            'public_profile_text' => 'Profil publik persona.',
            'identity_json' => ['usia' => '30', 'pekerjaan' => 'Karyawan'],
            'created_by' => $user->id,
            'created_at' => now(),
        ]);
        $persona->update(['current_version_id' => $version->id]);

        $scenario->currentVersion->assignedPersonas()->create([
            'persona_id' => $persona->id,
            'is_enabled' => true,
        ]);

        $response = $this->actingAs($user)->get(route('training.scenarios.briefing', $scenario));

        $response->assertOk();
        $response->assertSee($persona->name);
        $response->assertSee('Profil publik persona');
        $response->assertSee('Karyawan');
    }

    public function test_briefing_does_not_expose_hidden_persona_data(): void
    {
        $user = User::factory()->sales()->active()->create();

        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => ['CHOOSE_PERSONA'],
        ])->create();

        $persona = Persona::factory()->create(['status' => Persona::STATUS_ACTIVE]);
        $version = $persona->versions()->create([
            'version_number' => 1,
            'public_profile_text' => 'Profil publik.',
            'identity_json' => ['nama' => 'Test'],
            'human_behavior_traits_json' => ['interrupting' => 80],
            'created_by' => $user->id,
            'created_at' => now(),
        ]);
        $persona->update(['current_version_id' => $version->id]);

        $scenario->currentVersion->assignedPersonas()->create([
            'persona_id' => $persona->id,
            'is_enabled' => true,
        ]);

        $response = $this->actingAs($user)->get(route('training.scenarios.briefing', $scenario));

        $response->assertOk();
        $response->assertDontSee('interrupting');
        $response->assertDontSee('human_behavior_traits');
    }

    public function test_cannot_access_nonexistent_scenario(): void
    {
        $user = User::factory()->sales()->active()->create();

        $response = $this->actingAs($user)->get('/training/scenarios/99999');

        $response->assertNotFound();
    }

    public function test_disabled_persona_not_shown_in_choose_list(): void
    {
        $user = User::factory()->sales()->active()->create();

        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => ['CHOOSE_PERSONA'],
        ])->create();

        $enabledPersona = Persona::factory()->create(['status' => Persona::STATUS_ACTIVE]);
        $enabledVersion = $enabledPersona->versions()->create([
            'version_number' => 1,
            'public_profile_text' => 'Enabled',
            'identity_json' => ['nama' => 'A'],
            'created_by' => $user->id,
            'created_at' => now(),
        ]);
        $enabledPersona->update(['current_version_id' => $enabledVersion->id]);

        $disabledPersona = Persona::factory()->create(['status' => Persona::STATUS_ACTIVE]);
        $disabledVersion = $disabledPersona->versions()->create([
            'version_number' => 1,
            'public_profile_text' => 'Disabled',
            'identity_json' => ['nama' => 'B'],
            'created_by' => $user->id,
            'created_at' => now(),
        ]);
        $disabledPersona->update(['current_version_id' => $disabledVersion->id]);

        $scenario->currentVersion->assignedPersonas()->createMany([
            ['persona_id' => $enabledPersona->id, 'is_enabled' => true],
            ['persona_id' => $disabledPersona->id, 'is_enabled' => false],
        ]);

        $response = $this->actingAs($user)->get(route('training.scenarios.briefing', $scenario));

        $response->assertOk();
        $response->assertSee('Enabled');
        $response->assertDontSee('Disabled');
    }

    public function test_choose_persona_creates_session_with_selected_valid_persona(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => [PersonaMode::CHOOSE_PERSONA->value],
            'difficulty_level' => 'NORMAL',
        ])->create();
        $persona = $this->createAssignedPersona($scenario, $user, 'Persona Pilihan');

        $response = $this->actingAs($user)->post(route('training.scenarios.sessions.store', $scenario), $this->sessionPayload([
            'persona_mode' => PersonaMode::CHOOSE_PERSONA->value,
            'persona_id' => $persona->id,
        ]));

        $session = RoleplaySession::first();
        $response->assertRedirect(route('training.sessions.prepare', $session->public_id, absolute: false));

        $this->assertSame($user->id, $session->user_id);
        $this->assertSame($scenario->code, $session->scenario_id);
        $this->assertSame($persona->code, $session->persona_id);
        $this->assertSame(PersonaMode::CHOOSE_PERSONA->value, $session->persona_mode);
        $this->assertNotNull($session->snapshot);
    }

    public function test_choose_persona_rejects_invalid_unassigned_persona(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => [PersonaMode::CHOOSE_PERSONA->value],
        ])->create();
        $unassignedPersona = $this->createPersona($user, 'Persona Tidak Ditugaskan');

        $response = $this->actingAs($user)->from(route('training.scenarios.briefing', $scenario))->post(route('training.scenarios.sessions.store', $scenario), $this->sessionPayload([
            'persona_mode' => PersonaMode::CHOOSE_PERSONA->value,
            'persona_id' => $unassignedPersona->id,
        ]));

        $response->assertRedirect(route('training.scenarios.briefing', $scenario, absolute: false));
        $response->assertSessionHasErrors('persona_id');
        $this->assertDatabaseCount('roleplay_sessions', 0);
        $this->assertDatabaseCount('roleplay_session_snapshots', 0);
    }

    public function test_random_persona_creates_session(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => [PersonaMode::RANDOM_PERSONA->value],
        ])->create();
        $persona = $this->createAssignedPersona($scenario, $user, 'Persona Acak');

        $response = $this->actingAs($user)->post(route('training.scenarios.sessions.store', $scenario), $this->sessionPayload([
            'persona_mode' => PersonaMode::RANDOM_PERSONA->value,
        ]));

        $session = RoleplaySession::first();
        $response->assertRedirect(route('training.sessions.prepare', $session->public_id, absolute: false));
        $this->assertSame($persona->code, $session->persona_id);
        $this->assertSame(PersonaMode::RANDOM_PERSONA->value, $session->persona_mode);
    }

    public function test_hidden_persona_creates_session_without_exposing_selected_persona(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => [PersonaMode::HIDDEN_PERSONA->value],
        ])->create();
        $persona = $this->createAssignedPersona($scenario, $user, 'Nama Persona Rahasia', [
            'public_profile_text' => 'Profil rahasia tidak boleh tampil.',
            'identity_json' => ['pekerjaan' => 'Pekerjaan Rahasia'],
            'human_behavior_traits_json' => ['interrupting_tendency' => 90],
        ]);

        $response = $this->actingAs($user)->followingRedirects()->post(route('training.scenarios.sessions.store', $scenario), $this->sessionPayload([
            'persona_mode' => PersonaMode::HIDDEN_PERSONA->value,
        ]));

        $response->assertOk();
        $response->assertSee('Sesi latihan berhasil dibuat');
        $response->assertDontSee('Nama Persona Rahasia');
        $response->assertDontSee('Profil rahasia tidak boleh tampil');
        $response->assertDontSee('Pekerjaan Rahasia');
        $response->assertDontSee('interrupting_tendency');

        $this->assertSame($persona->code, RoleplaySession::first()->persona_id);
    }

    public function test_disallowed_persona_mode_is_rejected(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => [PersonaMode::CHOOSE_PERSONA->value],
        ])->create();

        $response = $this->actingAs($user)->from(route('training.scenarios.briefing', $scenario))->post(route('training.scenarios.sessions.store', $scenario), $this->sessionPayload([
            'persona_mode' => PersonaMode::RANDOM_PERSONA->value,
        ]));

        $response->assertRedirect(route('training.scenarios.briefing', $scenario, absolute: false));
        $response->assertSessionHasErrors('persona_mode');
        $this->assertDatabaseCount('roleplay_sessions', 0);
    }

    public function test_pending_and_suspended_users_cannot_create_sessions(): void
    {
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => [PersonaMode::RANDOM_PERSONA->value],
        ])->create();

        $pending = User::factory()->sales()->pendingApproval()->create();
        $suspended = User::factory()->sales()->suspended()->create();

        $this->actingAs($pending)->post(route('training.scenarios.sessions.store', $scenario), $this->sessionPayload([
            'persona_mode' => PersonaMode::RANDOM_PERSONA->value,
        ]))->assertRedirect(route('account.pending', absolute: false));

        $this->actingAs($suspended)->post(route('training.scenarios.sessions.store', $scenario), $this->sessionPayload([
            'persona_mode' => PersonaMode::RANDOM_PERSONA->value,
        ]))->assertRedirect(route('account.suspended', absolute: false));

        $this->assertDatabaseCount('roleplay_sessions', 0);
    }

    public function test_session_creation_creates_snapshot(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => [PersonaMode::RANDOM_PERSONA->value],
        ])->create();
        $this->createAssignedPersona($scenario, $user);

        $this->actingAs($user)->post(route('training.scenarios.sessions.store', $scenario), $this->sessionPayload([
            'persona_mode' => PersonaMode::RANDOM_PERSONA->value,
        ]));

        $this->assertDatabaseCount('roleplay_sessions', 1);
        $this->assertDatabaseCount('roleplay_session_snapshots', 1);
        $this->assertNotNull(RoleplaySession::first()->snapshot);
    }

    public function test_actor_instruction_hash_exists_after_session_creation(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => [PersonaMode::RANDOM_PERSONA->value],
        ])->create();
        $this->createAssignedPersona($scenario, $user);

        $this->actingAs($user)->post(route('training.scenarios.sessions.store', $scenario), $this->sessionPayload([
            'persona_mode' => PersonaMode::RANDOM_PERSONA->value,
        ]));

        $hash = RoleplaySessionSnapshot::first()->actor_instruction_hash;
        $this->assertSame(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    public function test_actor_instructions_are_encrypted_after_session_creation(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => [PersonaMode::RANDOM_PERSONA->value],
        ])->create();
        $this->createAssignedPersona($scenario, $user);

        $this->actingAs($user)->post(route('training.scenarios.sessions.store', $scenario), $this->sessionPayload([
            'persona_mode' => PersonaMode::RANDOM_PERSONA->value,
        ]));

        $snapshot = RoleplaySessionSnapshot::first();
        $raw = DB::table('roleplay_session_snapshots')->where('id', $snapshot->id)->value('actor_instructions');

        $this->assertStringContainsString('=== AKTOR PERSONA ===', $snapshot->actor_instructions);
        $this->assertNotSame($snapshot->actor_instructions, $raw);
        $this->assertStringStartsWith('eyJ', $raw);
    }

    public function test_hidden_data_not_exposed_in_session_creation_response(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => [PersonaMode::HIDDEN_PERSONA->value],
            'hidden_context' => 'konteks internal tidak tampil',
            'target_behaviors_json' => ['target_internal'],
            'prohibited_claims_json' => ['klaim_internal'],
        ])->create();
        $this->createAssignedPersona($scenario, $user, 'Persona Internal', [
            'public_profile_text' => 'Profil publik internal',
            'human_behavior_traits_json' => ['dominance' => 88],
        ]);

        $response = $this->actingAs($user)->followingRedirects()->post(route('training.scenarios.sessions.store', $scenario), $this->sessionPayload([
            'persona_mode' => PersonaMode::HIDDEN_PERSONA->value,
        ]));

        $response->assertOk();
        $response->assertDontSee('konteks internal tidak tampil');
        $response->assertDontSee('target_internal');
        $response->assertDontSee('klaim_internal');
        $response->assertDontSee('Persona Internal');
        $response->assertDontSee('Profil publik internal');
        $response->assertDontSee('dominance');
        $response->assertDontSee('AKTOR PERSONA');
        $response->assertDontSee('director_snapshot');
    }

    public function test_duplicate_choose_persona_submit_creates_only_one_session_and_redirects_same_session(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => [PersonaMode::CHOOSE_PERSONA->value],
        ])->create();
        $persona = $this->createAssignedPersona($scenario, $user);
        $key = (string) Str::uuid();
        $payload = $this->sessionPayload([
            'persona_mode' => PersonaMode::CHOOSE_PERSONA->value,
            'persona_id' => $persona->id,
        ], $key);

        $first = $this->actingAs($user)->post(route('training.scenarios.sessions.store', $scenario), $payload);
        $session = RoleplaySession::first();
        $second = $this->actingAs($user)->post(route('training.scenarios.sessions.store', $scenario), $payload);

        $first->assertRedirect(route('training.sessions.prepare', $session->public_id, absolute: false));
        $second->assertRedirect(route('training.sessions.prepare', $session->public_id, absolute: false));
        $this->assertDatabaseCount('roleplay_sessions', 1);
        $this->assertDatabaseCount('roleplay_session_snapshots', 1);
    }

    public function test_duplicate_random_persona_submit_creates_only_one_session(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => [PersonaMode::RANDOM_PERSONA->value],
        ])->create();
        $this->createAssignedPersona($scenario, $user, 'Persona A');
        $this->createAssignedPersona($scenario, $user, 'Persona B');
        $payload = $this->sessionPayload([
            'persona_mode' => PersonaMode::RANDOM_PERSONA->value,
        ], (string) Str::uuid());

        $this->actingAs($user)->post(route('training.scenarios.sessions.store', $scenario), $payload);
        $firstSession = RoleplaySession::first();
        $second = $this->actingAs($user)->post(route('training.scenarios.sessions.store', $scenario), $payload);

        $second->assertRedirect(route('training.sessions.prepare', $firstSession->public_id, absolute: false));
        $this->assertDatabaseCount('roleplay_sessions', 1);
        $this->assertDatabaseCount('roleplay_session_snapshots', 1);
    }

    public function test_duplicate_hidden_persona_submit_creates_only_one_session(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => [PersonaMode::HIDDEN_PERSONA->value],
        ])->create();
        $this->createAssignedPersona($scenario, $user, 'Persona Tersembunyi A');
        $this->createAssignedPersona($scenario, $user, 'Persona Tersembunyi B');
        $payload = $this->sessionPayload([
            'persona_mode' => PersonaMode::HIDDEN_PERSONA->value,
        ], (string) Str::uuid());

        $this->actingAs($user)->post(route('training.scenarios.sessions.store', $scenario), $payload);
        $firstSession = RoleplaySession::first();
        $second = $this->actingAs($user)->post(route('training.scenarios.sessions.store', $scenario), $payload);

        $second->assertRedirect(route('training.sessions.prepare', $firstSession->public_id, absolute: false));
        $this->assertDatabaseCount('roleplay_sessions', 1);
        $this->assertDatabaseCount('roleplay_session_snapshots', 1);
    }

    public function test_different_idempotency_key_creates_new_session(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => [PersonaMode::RANDOM_PERSONA->value],
        ])->create();
        $this->createAssignedPersona($scenario, $user);

        $this->actingAs($user)->post(route('training.scenarios.sessions.store', $scenario), $this->sessionPayload([
            'persona_mode' => PersonaMode::RANDOM_PERSONA->value,
        ], (string) Str::uuid()));
        $this->actingAs($user)->post(route('training.scenarios.sessions.store', $scenario), $this->sessionPayload([
            'persona_mode' => PersonaMode::RANDOM_PERSONA->value,
        ], (string) Str::uuid()));

        $this->assertDatabaseCount('roleplay_sessions', 2);
        $this->assertDatabaseCount('roleplay_session_snapshots', 2);
    }

    public function test_pending_and_suspended_users_still_blocked_with_idempotency_key(): void
    {
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => [PersonaMode::RANDOM_PERSONA->value],
        ])->create();

        $pending = User::factory()->sales()->pendingApproval()->create();
        $suspended = User::factory()->sales()->suspended()->create();

        $this->actingAs($pending)->post(route('training.scenarios.sessions.store', $scenario), $this->sessionPayload([
            'persona_mode' => PersonaMode::RANDOM_PERSONA->value,
        ], (string) Str::uuid()))->assertRedirect(route('account.pending', absolute: false));

        $this->actingAs($suspended)->post(route('training.scenarios.sessions.store', $scenario), $this->sessionPayload([
            'persona_mode' => PersonaMode::RANDOM_PERSONA->value,
        ], (string) Str::uuid()))->assertRedirect(route('account.suspended', absolute: false));

        $this->assertDatabaseCount('roleplay_sessions', 0);
    }

    public function test_briefing_form_includes_generated_idempotency_token(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => [PersonaMode::RANDOM_PERSONA->value],
        ])->create();

        $response = $this->actingAs($user)->get(route('training.scenarios.briefing', $scenario));

        $response->assertOk();
        $response->assertSee('name="idempotency_key"', false);
    }

    public function test_active_sales_can_view_own_prepare_page(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($user)->create();
        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
            'scenario_snapshot_json' => array_merge(
                RoleplaySessionSnapshot::factory()->definition()['scenario_snapshot_json'],
                ['name' => 'Latihan KPR Aman'],
            ),
        ]);

        $response = $this->actingAs($user)->get(route('training.sessions.prepare', $session->public_id));

        $response->assertOk();
        $response->assertViewIs('training.prepare');
        $response->assertSee('Latihan KPR Aman');
        $response->assertSee($session->public_id);
    }

    public function test_sales_cannot_view_another_users_prepare_page(): void
    {
        $owner = User::factory()->sales()->active()->create();
        $otherUser = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($owner)->create();
        RoleplaySessionSnapshot::factory()->create(['roleplay_session_id' => $session->id]);

        $response = $this->actingAs($otherUser)->get(route('training.sessions.prepare', $session->public_id));

        $response->assertNotFound();
    }

    public function test_pending_and_suspended_users_cannot_view_prepare_page(): void
    {
        $pending = User::factory()->sales()->pendingApproval()->create();
        $suspended = User::factory()->sales()->suspended()->create();
        $pendingSession = RoleplaySession::factory()->forUser($pending)->create();
        $suspendedSession = RoleplaySession::factory()->forUser($suspended)->create();

        $this->actingAs($pending)
            ->get(route('training.sessions.prepare', $pendingSession->public_id))
            ->assertRedirect(route('account.pending', absolute: false));

        $this->actingAs($suspended)
            ->get(route('training.sessions.prepare', $suspendedSession->public_id))
            ->assertRedirect(route('account.suspended', absolute: false));
    }

    public function test_prepare_page_does_not_expose_hidden_persona_details(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($user)->create([
            'persona_mode' => PersonaMode::HIDDEN_PERSONA->value,
            'persona_id' => 'PERSONA_RAHASIA',
        ]);
        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
            'persona_snapshot_json' => array_merge(
                RoleplaySessionSnapshot::factory()->definition()['persona_snapshot_json'],
                [
                    'name' => 'Nama Persona Rahasia',
                    'human_behavior_traits' => ['dominance' => 90],
                    'hidden_information' => [['information' => 'Info rahasia keluarga']],
                ],
            ),
        ]);

        $response = $this->actingAs($user)->get(route('training.sessions.prepare', $session->public_id));

        $response->assertOk();
        $response->assertDontSee('Nama Persona Rahasia');
        $response->assertDontSee('PERSONA_RAHASIA');
        $response->assertDontSee('dominance');
        $response->assertDontSee('Info rahasia keluarga');
    }

    public function test_prepare_page_does_not_expose_actor_instructions(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($user)->create();
        RoleplaySessionSnapshot::factory()->create([
            'roleplay_session_id' => $session->id,
            'actor_instructions' => "=== AKTOR PERSONA ===\nInstruksi internal rahasia.",
        ]);

        $response = $this->actingAs($user)->get(route('training.sessions.prepare', $session->public_id));

        $response->assertOk();
        $response->assertDontSee('AKTOR PERSONA');
        $response->assertDontSee('Instruksi internal rahasia');
        $response->assertDontSee('director_snapshot');
    }

    public function test_prepare_page_contains_microphone_ui_container(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($user)->create();
        RoleplaySessionSnapshot::factory()->create(['roleplay_session_id' => $session->id]);

        $response = $this->actingAs($user)->get(route('training.sessions.prepare', $session->public_id));

        $response->assertOk();
        $response->assertSee('id="microphone-permission-ui"', false);
        $response->assertSee('data-microphone-ui', false);
        $response->assertSee('Periksa Mikrofon');
        $response->assertSee('Coba Lagi');
    }

    public function test_prepare_page_includes_roleplay_runtime_container_and_credentials_endpoint(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($user)->create();
        RoleplaySessionSnapshot::factory()->create(['roleplay_session_id' => $session->id]);

        $response = $this->actingAs($user)->get(route('training.sessions.prepare', $session->public_id));

        $response->assertOk();
        $response->assertSee('id="roleplay-runtime"', false);
        $response->assertSee('data-roleplay-runtime', false);
        $response->assertSee('data-gemini-live-client="pending"', false);
        $response->assertSee('data-live-debug="false"', false);
        $response->assertSee('data-runtime-state="idle"', false);
        $response->assertSee('data-roleplay-start', false);
        $response->assertSee('data-roleplay-stop', false);
        $response->assertSee('Mulai Sesi');
        $response->assertSee(route('training.sessions.live-credentials.store', $session->public_id), false);
    }

    public function test_prepare_page_includes_microphone_capture_hooks(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($user)->create();
        RoleplaySessionSnapshot::factory()->create(['roleplay_session_id' => $session->id]);

        $response = $this->actingAs($user)->get(route('training.sessions.prepare', $session->public_id));

        $response->assertOk();
        $response->assertSee('data-microphone-capture="pending"', false);
        $response->assertSee('data-input-audio-format="pcm16-16000-le"', false);
        $response->assertSee('PCM 16 kHz');
    }

    public function test_prepare_page_includes_audio_streaming_hooks(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($user)->create();
        RoleplaySessionSnapshot::factory()->create(['roleplay_session_id' => $session->id]);

        $response = $this->actingAs($user)->get(route('training.sessions.prepare', $session->public_id));

        $response->assertOk();
        $response->assertSee('data-audio-stream="pending"', false);
        $response->assertSee('data-roleplay-stop', false);
        $response->assertSee('Hentikan Audio');
    }

    public function test_prepare_page_includes_ai_playback_hooks(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($user)->create();
        RoleplaySessionSnapshot::factory()->create(['roleplay_session_id' => $session->id]);

        $response = $this->actingAs($user)->get(route('training.sessions.prepare', $session->public_id));

        $response->assertOk();
        $response->assertSee('data-ai-playback="pending"', false);
        $response->assertSee('data-output-audio-format="pcm16-24000-le"', false);
        $response->assertSee('PCM 24 kHz');
    }

    public function test_prepare_page_includes_speaking_listening_state_hooks(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($user)->create();
        RoleplaySessionSnapshot::factory()->create(['roleplay_session_id' => $session->id]);

        $response = $this->actingAs($user)->get(route('training.sessions.prepare', $session->public_id));

        $response->assertOk();
        $response->assertSee('data-conversation-state="idle"', false);
        $response->assertSee('data-conversation-state-panel', false);
        $response->assertSee('data-conversation-status', false);
        $response->assertSee('data-conversation-indicator="listening"', false);
        $response->assertSee('data-conversation-indicator="user_speaking"', false);
        $response->assertSee('data-conversation-indicator="waiting_for_ai"', false);
        $response->assertSee('data-conversation-indicator="thinking"', false);
        $response->assertSee('data-conversation-indicator="ai_speaking"', false);
        $response->assertSee('data-conversation-indicator="interrupted"', false);
    }

    public function test_prepare_page_includes_barge_in_interruption_hooks(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($user)->create();
        RoleplaySessionSnapshot::factory()->create(['roleplay_session_id' => $session->id]);

        $response = $this->actingAs($user)->get(route('training.sessions.prepare', $session->public_id));

        $response->assertOk();
        $response->assertSee('data-barge-in="idle"', false);
        $response->assertSee('Terinterupsi');
        $response->assertSee('dihentikan saat Anda menyela');
    }

    public function test_prepare_page_includes_hidden_transcript_debug_hooks(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($user)->create();
        RoleplaySessionSnapshot::factory()->create(['roleplay_session_id' => $session->id]);

        $response = $this->actingAs($user)->get(route('training.sessions.prepare', $session->public_id));

        $response->assertOk();
        $response->assertSee('data-live-transcript="debug-hidden"', false);
        $response->assertSee('data-transcript-events="0"', false);
        $response->assertSee('data-transcript-latest-speaker="none"', false);
        $response->assertSee('data-transcript-latest-status="none"', false);
        $response->assertSee('data-live-transcript-panel', false);
        $response->assertSee('aria-hidden="true"', false);
        $response->assertSee('data-live-transcript-list', false);
        $response->assertSee('Transkrip tidak dikirim ke server pada tahap ini.');
    }

    public function test_prepare_page_includes_event_part_processing_hooks(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($user)->create();
        RoleplaySessionSnapshot::factory()->create(['roleplay_session_id' => $session->id]);

        $response = $this->actingAs($user)->get(route('training.sessions.prepare', $session->public_id));

        $response->assertOk();
        $response->assertSee('data-live-goaway="false"', false);
        $response->assertSee('data-live-goaway-reason="none"', false);
        $response->assertSee('data-live-goaway-reconnect="none"', false);
        $response->assertSee('data-live-toolcalls="0"', false);
        $response->assertSee('data-live-toolcall-latest="none"', false);
        $response->assertSee('panggilan fungsi');
    }

    public function test_prepare_page_does_not_write_ephemeral_token_to_dom(): void
    {
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($user)->create();
        RoleplaySessionSnapshot::factory()->create(['roleplay_session_id' => $session->id]);

        $response = $this->actingAs($user)->get(route('training.sessions.prepare', $session->public_id));

        $response->assertOk();
        $response->assertDontSee('ephemeral_token');
        $response->assertDontSee('authTokens/');
        $response->assertDontSee('access_token=');
    }

    public function test_prepare_page_does_not_expose_permanent_api_key(): void
    {
        config(['gemini.api_key' => 'server-secret-key']);
        $user = User::factory()->sales()->active()->create();
        $session = RoleplaySession::factory()->forUser($user)->create();
        RoleplaySessionSnapshot::factory()->create(['roleplay_session_id' => $session->id]);

        $response = $this->actingAs($user)->get(route('training.sessions.prepare', $session->public_id));

        $response->assertOk();
        $response->assertDontSee('server-secret-key');
        $response->assertDontSee('GEMINI_API_KEY');
    }

    private function createAssignedPersona(Scenario $scenario, User $user, string $name = 'Persona Aktif', array $versionData = []): Persona
    {
        $persona = $this->createPersona($user, $name, $versionData);

        $scenario->currentVersion->assignedPersonas()->create([
            'persona_id' => $persona->id,
            'is_enabled' => true,
        ]);

        return $persona;
    }

    private function sessionPayload(array $payload, ?string $key = null): array
    {
        return array_merge([
            'idempotency_key' => $key ?? (string) Str::uuid(),
        ], $payload);
    }

    private function createPersona(User $user, string $name = 'Persona Aktif', array $versionData = []): Persona
    {
        $persona = Persona::factory()->create([
            'name' => $name,
            'status' => Persona::STATUS_ACTIVE,
        ]);

        $version = $persona->versions()->create(array_merge([
            'version_number' => 1,
            'public_profile_text' => 'Profil publik persona.',
            'identity_json' => ['age' => '30', 'occupation' => 'Karyawan'],
            'housing_context_json' => [],
            'knowledge_beliefs_json' => [],
            'personality_profile_json' => [],
            'human_behavior_traits_json' => [],
            'communication_style_json' => [],
            'initial_dynamic_state_json' => [],
            'state_sensitivity_json' => [],
            'salience_overrides_json' => [],
            'created_by' => $user->id,
            'created_at' => now(),
        ], $versionData));

        $persona->update(['current_version_id' => $version->id]);

        return $persona;
    }
}
