<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\Scenario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
