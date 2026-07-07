<?php

namespace Tests\Feature;

use App\Models\Scenario;
use App\Models\ScenarioVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_user_can_view_training_dashboard(): void
    {
        $user = User::factory()->sales()->active()->create();
        Scenario::factory()->count(3)->withVersion()->create();

        $response = $this->actingAs($user)->get(route('training.dashboard'));

        $response->assertOk();
        $response->assertViewIs('training.dashboard');
    }

    public function test_pending_user_cannot_view_training_dashboard(): void
    {
        $user = User::factory()->sales()->pendingApproval()->create();

        $response = $this->actingAs($user)->get(route('training.dashboard'));

        $response->assertRedirect(route('account.pending', absolute: false));
    }

    public function test_suspended_user_cannot_view_training_dashboard(): void
    {
        $user = User::factory()->sales()->suspended()->create();

        $response = $this->actingAs($user)->get(route('training.dashboard'));

        $response->assertRedirect(route('account.suspended', absolute: false));
    }

    public function test_dashboard_shows_only_active_scenarios(): void
    {
        $user = User::factory()->sales()->active()->create();
        Scenario::factory()->count(2)->withVersion()->create();
        Scenario::factory()->count(1)->archived()->withVersion()->create();

        $response = $this->actingAs($user)->get(route('training.dashboard'));

        $response->assertOk();
        $response->assertViewHas('scenarios', function ($scenarios) {
            return $scenarios->count() === 2
                && $scenarios->every(fn ($s) => $s->isActive());
        });
    }

    public function test_dashboard_lists_scenario_names_and_difficulty(): void
    {
        $user = User::factory()->sales()->active()->create();
        $scenario = Scenario::factory()->withVersion([
            'description' => 'Latihan negasi penawaran',
            'difficulty_level' => 'NORMAL',
        ])->create();

        $response = $this->actingAs($user)->get(route('training.dashboard'));

        $response->assertOk();
        $response->assertSee($scenario->name);
        $response->assertSee('NORMAL');
        $response->assertSee('Latihan negasi penawaran');
    }

    public function test_dashboard_does_not_expose_hidden_scenario_fields(): void
    {
        $user = User::factory()->sales()->active()->create();
        Scenario::factory()->withVersion([
            'hidden_context' => 'rahasia tersembunyi',
            'target_behaviors_json' => ['behaviour_x'],
            'sales_briefing' => 'internal briefing',
            'training_objective' => 'melatih negasi',
            'success_conditions_json' => ['condition_a'],
            'failure_conditions_json' => ['condition_b'],
        ])->create();

        $response = $this->actingAs($user)->get(route('training.dashboard'));

        $response->assertOk();
        $response->assertDontSee('rahasia tersembunyi');
        $response->assertDontSee('behaviour_x');
        $response->assertDontSee('internal briefing');
        $response->assertDontSee('melatih negasi');
        $response->assertDontSee('condition_a');
        $response->assertDontSee('condition_b');
    }

    public function test_dashboard_shows_persona_modes(): void
    {
        $user = User::factory()->sales()->active()->create();
        Scenario::factory()->withVersion([
            'allowed_persona_modes_json' => ['CHOOSE_PERSONA', 'RANDOM_PERSONA'],
        ])->create();

        $response = $this->actingAs($user)->get(route('training.dashboard'));

        $response->assertOk();
        $response->assertSee('Pilih Persona');
        $response->assertSee('Persona Acak');
    }

    public function test_dashboard_shows_max_duration(): void
    {
        $user = User::factory()->sales()->active()->create();
        Scenario::factory()->withVersion([
            'max_duration_seconds' => 300,
        ])->create();

        $response = $this->actingAs($user)->get(route('training.dashboard'));

        $response->assertOk();
        $response->assertSee('05:00');
    }

    public function test_sales_user_sees_latihan_nav_link(): void
    {
        $user = User::factory()->sales()->active()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Latihan');
    }
}
