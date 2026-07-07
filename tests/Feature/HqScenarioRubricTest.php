<?php

namespace Tests\Feature;

use App\Models\EvaluationRubric;
use App\Models\Scenario;
use App\Models\User;
use Database\Factories\EvaluationRubricFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HqScenarioRubricTest extends TestCase
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

    private function createScenarioWithVersion(): Scenario
    {
        return Scenario::factory()->withVersion()->create();
    }

    public function test_super_admin_can_view_scenario_rubric_page(): void
    {
        $scenario = $this->createScenarioWithVersion();

        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.scenario-rubrics.edit', $scenario));

        $response->assertOk();
        $response->assertSee('Rubrik Skenario');
    }

    public function test_sales_cannot_view_scenario_rubric_page(): void
    {
        $scenario = $this->createScenarioWithVersion();

        $response = $this->actingAs($this->sales)
            ->get(route('hq.scenario-rubrics.edit', $scenario));

        $response->assertForbidden();
    }

    public function test_super_admin_can_create_scenario_rubric(): void
    {
        $scenario = $this->createScenarioWithVersion();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.scenario-rubrics.update', $scenario), [
                'name' => 'Scenario Rubrik Test',
                'items' => [
                    [
                        'key' => 'sr_item_1',
                        'title' => 'Item Skenario 1',
                        'weight' => 100,
                    ],
                ],
            ]);

        $response->assertRedirect(route('hq.scenario-rubrics.edit', $scenario));

        $versionId = $scenario->fresh()->current_version_id;

        $this->assertDatabaseHas('evaluation_rubrics', [
            'name' => 'Scenario Rubrik Test',
            'type' => 'SCENARIO',
            'scenario_version_id' => $versionId,
        ]);

        $rubric = EvaluationRubric::where('scenario_version_id', $versionId)
            ->where('type', 'SCENARIO')
            ->first();

        $this->assertNotNull($rubric);
        $this->assertDatabaseHas('evaluation_rubric_items', [
            'evaluation_rubric_id' => $rubric->id,
            'key' => 'sr_item_1',
        ]);
    }

    public function test_sales_cannot_create_scenario_rubric(): void
    {
        $scenario = $this->createScenarioWithVersion();

        $response = $this->actingAs($this->sales)
            ->post(route('hq.scenario-rubrics.update', $scenario), [
                'name' => 'Hacked Rubric',
            ]);

        $response->assertForbidden();
    }

    public function test_can_update_scenario_rubric_items(): void
    {
        $scenario = $this->createScenarioWithVersion();
        $versionId = $scenario->fresh()->current_version_id;

        $rubric = EvaluationRubricFactory::new()->scenario()->create([
            'scenario_version_id' => $versionId,
            'name' => 'Awal',
        ]);

        $rubric->items()->create([
            'key' => 'old_key',
            'title' => 'Item Lama',
            'weight' => 50,
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('hq.scenario-rubrics.update', $scenario), [
                'name' => 'Diperbarui',
                'items' => [
                    [
                        'key' => 'new_key',
                        'title' => 'Item Baru',
                        'weight' => 100,
                    ],
                ],
            ]);

        $this->assertDatabaseMissing('evaluation_rubric_items', [
            'key' => 'old_key',
        ]);

        $this->assertDatabaseHas('evaluation_rubric_items', [
            'evaluation_rubric_id' => $rubric->id,
            'key' => 'new_key',
        ]);
    }

    public function test_can_set_overrides_on_scenario_rubric(): void
    {
        $scenario = $this->createScenarioWithVersion();

        $globalRubric = EvaluationRubricFactory::new()->create(['name' => 'Global Utama']);
        $globalRubric->items()->create([
            'key' => 'g_fluency',
            'title' => 'Kefasihan',
            'weight' => 100,
        ]);

        $versionId = $scenario->fresh()->current_version_id;

        $this->actingAs($this->superAdmin)
            ->post(route('hq.scenario-rubrics.update', $scenario), [
                'name' => 'Dengan Override',
                'items' => [
                    [
                        'key' => 'sr_custom',
                        'title' => 'Item Custom',
                        'weight' => 100,
                    ],
                ],
                'overrides' => [
                    [
                        'global_rubric_item_key' => 'g_fluency',
                        'weight_override' => 150,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('scenario_rubric_overrides', [
            'scenario_version_id' => $versionId,
            'global_rubric_item_key' => 'g_fluency',
            'weight_override' => 150,
        ]);
    }

    public function test_overrides_are_replaced_on_update(): void
    {
        $scenario = $this->createScenarioWithVersion();

        $globalRubric = EvaluationRubricFactory::new()->create(['name' => 'Global']);
        $globalRubric->items()->createMany([
            ['key' => 'g_a', 'title' => 'A', 'weight' => 100],
            ['key' => 'g_b', 'title' => 'B', 'weight' => 100],
        ]);

        $versionId = $scenario->fresh()->current_version_id;

        $this->actingAs($this->superAdmin)
            ->post(route('hq.scenario-rubrics.update', $scenario), [
                'name' => 'Override Test',
                'items' => [['key' => 'x', 'title' => 'X', 'weight' => 100]],
                'overrides' => [
                    ['global_rubric_item_key' => 'g_a', 'weight_override' => 200],
                ],
            ]);

        $this->assertEquals(1, $scenario->fresh()->currentVersion->rubricOverrides->count());

        $this->actingAs($this->superAdmin)
            ->post(route('hq.scenario-rubrics.update', $scenario), [
                'name' => 'Override Test',
                'items' => [['key' => 'x', 'title' => 'X', 'weight' => 100]],
                'overrides' => [
                    ['global_rubric_item_key' => 'g_b', 'weight_override' => 300],
                ],
            ]);

        $this->assertDatabaseMissing('scenario_rubric_overrides', [
            'global_rubric_item_key' => 'g_a',
        ]);

        $this->assertDatabaseHas('scenario_rubric_overrides', [
            'scenario_version_id' => $versionId,
            'global_rubric_item_key' => 'g_b',
            'weight_override' => 300,
        ]);
    }

    public function test_guest_cannot_access_scenario_rubric(): void
    {
        $scenario = $this->createScenarioWithVersion();

        $response = $this->get(route('hq.scenario-rubrics.edit', $scenario));
        $response->assertRedirect(route('login'));
    }

    public function test_scenario_rubric_uses_current_version(): void
    {
        $scenario = $this->createScenarioWithVersion();

        $this->actingAs($this->superAdmin)
            ->post(route('hq.scenario-rubrics.update', $scenario), [
                'name' => 'Version Locked Rubric',
                'items' => [
                    ['key' => 'v1', 'title' => 'V1', 'weight' => 100],
                ],
            ]);

        $versionId = $scenario->fresh()->current_version_id;

        $this->assertDatabaseHas('evaluation_rubrics', [
            'scenario_version_id' => $versionId,
            'type' => 'SCENARIO',
        ]);
    }

    public function test_global_rubrics_appear_on_scenario_rubric_page(): void
    {
        $scenario = $this->createScenarioWithVersion();

        $global = EvaluationRubricFactory::new()->create(['name' => 'Global Reference']);
        $global->items()->create([
            'key' => 'ref_item',
            'title' => 'Item Referensi',
            'weight' => 100,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.scenario-rubrics.edit', $scenario));

        $response->assertSee('Global Reference');
        $response->assertSee('Item Referensi');
    }
}
