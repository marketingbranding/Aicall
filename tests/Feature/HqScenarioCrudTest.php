<?php

namespace Tests\Feature;

use App\Models\EvaluationRubric;
use App\Models\Persona;
use App\Models\Scenario;
use App\Models\ScenarioVersion;
use App\Models\User;
use Database\Factories\EvaluationRubricFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HqScenarioCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->sales = User::factory()->sales()->create();
    }

    public function test_super_admin_can_view_scenario_list(): void
    {
        $scenario = Scenario::factory()->create(['created_by' => $this->superAdmin->id]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.scenarios.index'));

        $response->assertOk();
        $response->assertSee($scenario->name);
    }

    public function test_sales_cannot_view_scenario_list(): void
    {
        $response = $this->actingAs($this->sales)
            ->get(route('hq.scenarios.index'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_scenario_list(): void
    {
        $response = $this->get(route('hq.scenarios.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_create_form(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.scenarios.create'));

        $response->assertOk();
        $response->assertSee('Buat Skenario Baru');
    }

    public function test_sales_cannot_view_create_form(): void
    {
        $response = $this->actingAs($this->sales)
            ->get(route('hq.scenarios.create'));

        $response->assertForbidden();
    }

    public function test_super_admin_can_create_scenario(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.scenarios.store'), [
                'code' => 'TLPN_PERTAMA',
                'name' => 'Telepon Pertama Masuk',
                'description' => 'Skenario panggilan pertama dari lead.',
                'sales_briefing' => 'Lead dari TikTok.',
                'first_speaker' => 'AI',
                'difficulty_level' => 'NORMAL',
                'max_duration_seconds' => 600,
            ]);

        $response->assertRedirect(route('hq.scenarios.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('scenarios', [
            'code' => 'TLPN_PERTAMA',
            'name' => 'Telepon Pertama Masuk',
            'status' => Scenario::STATUS_ACTIVE,
        ]);

        $scenario = Scenario::where('code', 'TLPN_PERTAMA')->first();
        $this->assertNotNull($scenario->currentVersion);
        $this->assertEquals(1, $scenario->currentVersion->version_number);
        $this->assertEquals('Telepon Pertama Masuk', $scenario->name);
        $this->assertEquals('Skenario panggilan pertama dari lead.', $scenario->currentVersion->description);
        $this->assertEquals('NORMAL', $scenario->currentVersion->difficulty_level);
        $this->assertEquals(600, $scenario->currentVersion->max_duration_seconds);
        $this->assertEquals('AI', $scenario->currentVersion->first_speaker);
    }

    public function test_sales_cannot_create_scenario(): void
    {
        $response = $this->actingAs($this->sales)
            ->post(route('hq.scenarios.store'), [
                'code' => 'TLPN_PERTAMA',
                'name' => 'Telepon Pertama Masuk',
            ]);

        $response->assertForbidden();
    }

    public function test_create_scenario_requires_unique_code(): void
    {
        Scenario::factory()->create([
            'code' => 'TLPN_PERTAMA',
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.scenarios.store'), [
                'code' => 'TLPN_PERTAMA',
                'name' => 'Skenario Lain',
            ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_create_scenario_rejects_invalid_first_speaker(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.scenarios.store'), [
                'code' => 'TEST',
                'name' => 'Test',
                'first_speaker' => 'INVALID',
            ]);

        $response->assertSessionHasErrors('first_speaker');
    }

    public function test_create_scenario_rejects_invalid_difficulty(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.scenarios.store'), [
                'code' => 'TEST',
                'name' => 'Test',
                'difficulty_level' => 'INVALID',
            ]);

        $response->assertSessionHasErrors('difficulty_level');
    }

    public function test_create_scenario_rejects_duration_over_900(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.scenarios.store'), [
                'code' => 'TEST',
                'name' => 'Test',
                'max_duration_seconds' => 901,
            ]);

        $response->assertSessionHasErrors('max_duration_seconds');
    }

    public function test_super_admin_can_view_edit_form(): void
    {
        $scenario = Scenario::factory()->create(['created_by' => $this->superAdmin->id]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.scenarios.edit', $scenario));

        $response->assertOk();
        $response->assertSee($scenario->name);
        $response->assertSee('Edit Skenario');
    }

    public function test_super_admin_can_update_scenario(): void
    {
        $scenario = Scenario::factory()->create(['created_by' => $this->superAdmin->id]);
        $version = ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'description' => 'Deskripsi asli',
            'first_speaker' => 'AI',
            'difficulty_level' => 'NORMAL',
            'created_by' => $this->superAdmin->id,
            'created_at' => now(),
        ]);
        $scenario->update(['current_version_id' => $version->id]);

        $response = $this->actingAs($this->superAdmin)
            ->from(route('hq.scenarios.edit', $scenario))
            ->put(route('hq.scenarios.update', $scenario), [
                'code' => 'TLPN_UPDATED',
                'name' => 'Skenario Update',
                'description' => 'Deskripsi baru',
                'first_speaker' => 'AI',
                'difficulty_level' => 'DIFFICULT',
            ]);

        $response->assertRedirect(route('hq.scenarios.index'));
        $response->assertSessionHas('success');

        $scenario->refresh();
        $this->assertEquals('TLPN_UPDATED', $scenario->code);
        $this->assertEquals('Skenario Update', $scenario->name);

        $this->assertEquals(2, $scenario->currentVersion->version_number);
        $this->assertEquals('Deskripsi baru', $scenario->currentVersion->description);
        $this->assertEquals('DIFFICULT', $scenario->currentVersion->difficulty_level);
    }

    public function test_sales_cannot_update_scenario(): void
    {
        $scenario = Scenario::factory()->create();

        $response = $this->actingAs($this->sales)
            ->put(route('hq.scenarios.update', $scenario), [
                'code' => 'NEW_CODE',
                'name' => 'New Name',
            ]);

        $response->assertForbidden();
    }

    public function test_super_admin_can_archive_scenario(): void
    {
        $scenario = Scenario::factory()->create(['created_by' => $this->superAdmin->id]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.scenarios.archive', $scenario));

        $response->assertRedirect(route('hq.scenarios.index'));
        $response->assertSessionHas('success');

        $this->assertTrue($scenario->fresh()->isArchived());
    }

    public function test_sales_cannot_archive_scenario(): void
    {
        $scenario = Scenario::factory()->create();

        $response = $this->actingAs($this->sales)
            ->post(route('hq.scenarios.archive', $scenario));

        $response->assertForbidden();
    }

    public function test_super_admin_can_duplicate_scenario(): void
    {
        $scenario = Scenario::factory()->create(['created_by' => $this->superAdmin->id]);
        $version = ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'description' => 'Deskripsi asli',
            'first_speaker' => 'AI',
            'difficulty_level' => 'NORMAL',
            'created_by' => $this->superAdmin->id,
            'created_at' => now(),
        ]);
        $scenario->update(['current_version_id' => $version->id]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.scenarios.duplicate', $scenario));

        $response->assertRedirect(route('hq.scenarios.index'));
        $response->assertSessionHas('success');

        $clones = Scenario::where('name', 'LIKE', $scenario->name . '%')
            ->where('id', '!=', $scenario->id)
            ->get();

        $this->assertCount(1, $clones);
        $this->assertTrue($clones->first()->isActive());
        $this->assertNotNull($clones->first()->currentVersion);
    }

    public function test_duplicate_preserves_rubric_overrides(): void
    {
        $scenario = Scenario::factory()->create(['created_by' => $this->superAdmin->id]);
        $version = ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'description' => 'Deskripsi dengan override',
            'first_speaker' => 'AI',
            'difficulty_level' => 'NORMAL',
            'created_by' => $this->superAdmin->id,
            'created_at' => now(),
        ]);
        $scenario->update(['current_version_id' => $version->id]);

        $version->rubricOverrides()->create([
            'global_rubric_item_key' => 'g_fluency',
            'weight_override' => 150,
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('hq.scenarios.duplicate', $scenario));

        $clone = Scenario::where('name', 'LIKE', $scenario->name . '%')
            ->where('id', '!=', $scenario->id)
            ->first();

        $this->assertNotNull($clone);
        $overrides = $clone->currentVersion->rubricOverrides;
        $this->assertCount(1, $overrides);
        $this->assertEquals('g_fluency', $overrides->first()->global_rubric_item_key);
        $this->assertEquals(150, $overrides->first()->weight_override);
    }

    public function test_duplicate_preserves_scenario_rubric(): void
    {
        $scenario = Scenario::factory()->create(['created_by' => $this->superAdmin->id]);
        $version = ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'description' => 'Deskripsi dengan rubric',
            'first_speaker' => 'AI',
            'difficulty_level' => 'NORMAL',
            'created_by' => $this->superAdmin->id,
            'created_at' => now(),
        ]);
        $scenario->update(['current_version_id' => $version->id]);

        $rubric = EvaluationRubricFactory::new()->scenario()->create([
            'scenario_version_id' => $version->id,
            'name' => 'Rubrik Skenario Asli',
        ]);
        $rubric->items()->create([
            'key' => 'custom_item',
            'title' => 'Item Kustom',
            'weight' => 100,
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('hq.scenarios.duplicate', $scenario));

        $clone = Scenario::where('name', 'LIKE', $scenario->name . '%')
            ->where('id', '!=', $scenario->id)
            ->first();

        $this->assertNotNull($clone);
        $cloneRubric = EvaluationRubric::where('type', EvaluationRubric::TYPE_SCENARIO)
            ->where('scenario_version_id', $clone->currentVersion->id)
            ->with('items')
            ->first();

        $this->assertNotNull($cloneRubric);
        $this->assertEquals('Rubrik Skenario Asli', $cloneRubric->name);
        $this->assertCount(1, $cloneRubric->items);
        $this->assertEquals('custom_item', $cloneRubric->items->first()->key);
    }

    public function test_archived_scenario_appears_in_list(): void
    {
        $active = Scenario::factory()->create(['created_by' => $this->superAdmin->id]);
        $archived = Scenario::factory()->archived()->create(['created_by' => $this->superAdmin->id]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.scenarios.index'));

        $response->assertSee($active->name);
        $response->assertSee($archived->name);
        $response->assertSee('Diarsipkan');
    }

    public function test_empty_state_when_no_scenarios(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.scenarios.index'));

        $response->assertOk();
        $response->assertSee('Belum ada skenario');
    }

    public function test_update_scenario_creates_new_version(): void
    {
        $scenario = Scenario::factory()->create(['created_by' => $this->superAdmin->id]);
        $v1 = ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'description' => 'Versi 1',
            'first_speaker' => 'AI',
            'difficulty_level' => 'NORMAL',
            'created_by' => $this->superAdmin->id,
            'created_at' => now(),
        ]);
        $scenario->update(['current_version_id' => $v1->id]);

        $this->actingAs($this->superAdmin)
            ->put(route('hq.scenarios.update', $scenario), [
                'code' => $scenario->code,
                'name' => $scenario->name,
                'description' => 'Versi 2',
                'first_speaker' => 'AI',
                'difficulty_level' => 'NORMAL',
            ]);

        $this->assertEquals(2, $scenario->fresh()->currentVersion->version_number);
        $this->assertDatabaseHas('scenario_versions', [
            'scenario_id' => $scenario->id,
            'version_number' => 2,
        ]);
        $this->assertDatabaseHas('scenario_versions', [
            'scenario_id' => $scenario->id,
            'version_number' => 1,
        ]);
    }

    public function test_create_scenario_with_all_config_options(): void
    {
        $persona = Persona::factory()->create(['created_by' => $this->superAdmin->id]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.scenarios.store'), [
                'code' => 'FULL_TEST',
                'name' => 'Skenario Full',
                'description' => 'Deskripsi lengkap',
                'sales_briefing' => 'Briefing sales',
                'hidden_context' => 'Konteks rahasia',
                'training_objective' => 'Tujuan training',
                'starting_phase' => 'DISCOVERY',
                'first_speaker' => 'USER',
                'ai_opening_context' => 'Konteks pembuka AI',
                'initial_customer_intent' => 'Ingin cari rumah',
                'target_behaviors_text' => 'active listening, discovery, empathy',
                'discovery_points_text' => 'anggaran, lokasi',
                'mandatory_topics_text' => 'KPR subsidi, cicilan',
                'prohibited_claims_text' => 'jaminan ACC',
                'success_conditions_text' => 'menemukan kebutuhan, closing',
                'failure_conditions_text' => 'mengabaikan concern, klaim palsu',
                'difficulty_level' => 'CUSTOM',
                'difficulty_config' => [
                    'trust_gain_multiplier' => '0.5',
                    'trust_loss_multiplier' => '1.5',
                ],
                'max_duration_seconds' => 900,
                'allow_ai_end_call' => '1',
                'allowed_persona_modes' => ['CHOOSE_PERSONA', 'HIDDEN_PERSONA'],
                'persona_ids' => [$persona->id],
            ]);

        $response->assertRedirect(route('hq.scenarios.index'));
        $response->assertSessionHas('success');

        $scenario = Scenario::where('code', 'FULL_TEST')->first();
        $version = $scenario->currentVersion;

        $this->assertEquals('USER', $version->first_speaker);
        $this->assertEquals('DISCOVERY', $version->starting_phase);
        $this->assertEquals('CUSTOM', $version->difficulty_level);
        $this->assertEquals(900, $version->max_duration_seconds);
        $this->assertTrue($version->allow_ai_end_call);
        $this->assertEquals(['active listening', 'discovery', 'empathy'], $version->target_behaviors_json);
        $this->assertEquals(['CHOOSE_PERSONA', 'HIDDEN_PERSONA'], $version->allowed_persona_modes_json);
        $this->assertEquals(['trust_gain_multiplier' => '0.5', 'trust_loss_multiplier' => '1.5'], $version->difficulty_config_json);

        $this->assertDatabaseHas('scenario_personas', [
            'scenario_version_id' => $version->id,
            'persona_id' => $persona->id,
        ]);
    }

    public function test_archived_scenario_cannot_be_edited(): void
    {
        $scenario = Scenario::factory()->archived()->create(['created_by' => $this->superAdmin->id]);

        $response = $this->actingAs($this->superAdmin)
            ->put(route('hq.scenarios.update', $scenario), [
                'code' => 'NEW_CODE',
                'name' => 'New Name',
            ]);

        $response->assertForbidden();
    }

    public function test_archived_scenario_cannot_be_archived_again(): void
    {
        $scenario = Scenario::factory()->archived()->create(['created_by' => $this->superAdmin->id]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.scenarios.archive', $scenario));

        $response->assertForbidden();
    }
}
