<?php

namespace Tests\Unit;

use App\Models\EvaluationRubric;
use App\Models\EvaluationRubricItem;
use App\Models\ScenarioRubricOverride;
use App\Models\ScenarioVersion;
use App\Services\Rubrics\MergedRubricItem;
use App\Services\Rubrics\RubricMerger;
use App\Services\Rubrics\RubricMergedResult;
use App\Models\Scenario;
use Database\Factories\EvaluationRubricFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RubricMergerTest extends TestCase
{
    use RefreshDatabase;

    private RubricMerger $merger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merger = new RubricMerger;
    }

    private function createScenarioWithVersion(): ScenarioVersion
    {
        $scenario = \App\Models\Scenario::factory()->withVersion()->create();

        return $scenario->currentVersion;
    }

    public function test_global_rubric_items_are_included(): void
    {
        $global = EvaluationRubricFactory::new()->create(['name' => 'Global']);
        $global->items()->createMany([
            ['key' => 'opening', 'title' => 'Opening', 'weight' => 100],
            ['key' => 'closing', 'title' => 'Closing', 'weight' => 100],
        ]);

        $result = $this->merger->merge(
            collect([$global]),
            null,
            collect(),
        );

        $this->assertCount(2, $result->items);
        $this->assertSame('global', $result->items[0]->source); // sorted: closing, opening
    }

    public function test_disabled_global_items_are_marked_disabled(): void
    {
        $global = EvaluationRubricFactory::new()->create(['name' => 'Global']);
        $global->items()->createMany([
            ['key' => 'active_item', 'title' => 'Aktif', 'weight' => 100, 'is_enabled' => true],
            ['key' => 'disabled_item', 'title' => 'Nonaktif', 'weight' => 50, 'is_enabled' => false],
        ]);

        $result = $this->merger->merge(
            collect([$global]),
            null,
            collect(),
        );

        $this->assertCount(2, $result->items);

        $active = collect($result->items)->firstWhere('key', 'active_item');
        $disabled = collect($result->items)->firstWhere('key', 'disabled_item');

        $this->assertTrue($active->isEnabled);
        $this->assertFalse($disabled->isEnabled);
    }

    public function test_scenario_specific_items_are_included(): void
    {
        $global = EvaluationRubricFactory::new()->create(['name' => 'Global']);
        $global->items()->create(['key' => 'global_item', 'title' => 'Global', 'weight' => 100]);

        $scenarioRubric = EvaluationRubricFactory::new()->scenario()->create(['name' => 'Scenario']);
        $scenarioRubric->items()->create(['key' => 'scenario_item', 'title' => 'Scenario', 'weight' => 80]);

        $result = $this->merger->merge(
            collect([$global]),
            $scenarioRubric,
            collect(),
        );

        $this->assertCount(2, $result->items);
        $this->assertSame('scenario', collect($result->items)->firstWhere('key', 'scenario_item')->source);
    }

    public function test_weight_overrides_are_applied(): void
    {
        $global = EvaluationRubricFactory::new()->create(['name' => 'Global']);
        $global->items()->create(['key' => 'discovery', 'title' => 'Discovery', 'weight' => 100]);

        $version = $this->createScenarioWithVersion();
        $version->rubricOverrides()->create([
            'global_rubric_item_key' => 'discovery',
            'weight_override' => 150,
        ]);

        $result = $this->merger->merge(
            collect([$global]),
            null,
            $version->rubricOverrides,
        );

        $item = collect($result->items)->firstWhere('key', 'discovery');
        $this->assertSame(150, $item->weight);
    }

    public function test_disabled_override_is_respected(): void
    {
        $global = EvaluationRubricFactory::new()->create(['name' => 'Global']);
        $global->items()->create(['key' => 'rapport', 'title' => 'Rapport', 'weight' => 100, 'is_enabled' => true]);

        $version = $this->createScenarioWithVersion();
        $version->rubricOverrides()->create([
            'global_rubric_item_key' => 'rapport',
            'is_enabled_override' => false,
        ]);

        $result = $this->merger->merge(
            collect([$global]),
            null,
            $version->rubricOverrides,
        );

        $item = collect($result->items)->firstWhere('key', 'rapport');
        $this->assertFalse($item->isEnabled);
    }

    public function test_output_is_deterministic(): void
    {
        $global = EvaluationRubricFactory::new()->create(['name' => 'Global']);
        $global->items()->createMany([
            ['key' => 'z_item', 'title' => 'Z', 'weight' => 100],
            ['key' => 'a_item', 'title' => 'A', 'weight' => 100],
            ['key' => 'm_item', 'title' => 'M', 'weight' => 100],
        ]);

        $result1 = $this->merger->merge(collect([$global]), null, collect());
        $result2 = $this->merger->merge(collect([$global]), null, collect());

        $keys1 = array_map(fn(MergedRubricItem $i) => $i->key, $result1->items);
        $keys2 = array_map(fn(MergedRubricItem $i) => $i->key, $result2->items);

        $this->assertSame($keys1, $keys2);
        $this->assertSame(['a_item', 'm_item', 'z_item'], $keys1);
    }

    public function test_output_is_snapshot_safe(): void
    {
        $global = EvaluationRubricFactory::new()->create(['name' => 'Global']);
        $global->items()->create([
            'key' => 'test', 'title' => 'Test',
            'description' => 'Desc', 'weight' => 100,
            'is_enabled' => true, 'evaluation_guidance' => 'Guide',
        ]);

        $result = $this->merger->merge(collect([$global]), null, collect());

        $array = $result->toArray();
        $json = json_encode($array);

        $this->assertIsArray($array);
        $this->assertNotFalse($json);

        $this->assertArrayHasKey('key', $array[0]);
        $this->assertArrayHasKey('title', $array[0]);
        $this->assertArrayHasKey('weight', $array[0]);
        $this->assertArrayHasKey('is_enabled', $array[0]);
        $this->assertArrayHasKey('source', $array[0]);
    }

    public function test_enabled_items_filter(): void
    {
        $global = EvaluationRubricFactory::new()->create(['name' => 'Global']);
        $global->items()->createMany([
            ['key' => 'a', 'title' => 'A', 'weight' => 100, 'is_enabled' => true],
            ['key' => 'b', 'title' => 'B', 'weight' => 100, 'is_enabled' => false],
        ]);

        $result = $this->merger->merge(collect([$global]), null, collect());

        $enabled = $result->enabledItems();
        $this->assertCount(1, $enabled);
        $this->assertSame('a', $enabled[0]->key);
    }

    public function test_multiple_active_global_rubrics_are_merged(): void
    {
        $globalA = EvaluationRubricFactory::new()->create(['name' => 'Global A']);
        $globalA->items()->create(['key' => 'opening', 'title' => 'Opening', 'weight' => 100]);

        $globalB = EvaluationRubricFactory::new()->create(['name' => 'Global B']);
        $globalB->items()->create(['key' => 'closing', 'title' => 'Closing', 'weight' => 80]);

        $result = $this->merger->merge(
            collect([$globalA, $globalB]),
            null,
            collect(),
        );

        $this->assertCount(2, $result->items);
    }

    public function test_empty_inputs_return_empty_result(): void
    {
        $result = $this->merger->merge(collect(), null, collect());

        $this->assertEmpty($result->items);
        $this->assertEmpty($result->enabledItems());
        $this->assertSame([], $result->toArray());
    }

    public function test_override_does_not_affect_unrelated_items(): void
    {
        $global = EvaluationRubricFactory::new()->create(['name' => 'Global']);
        $global->items()->createMany([
            ['key' => 'item_a', 'title' => 'A', 'weight' => 100],
            ['key' => 'item_b', 'title' => 'B', 'weight' => 50],
        ]);

        $version = $this->createScenarioWithVersion();
        $version->rubricOverrides()->create([
            'global_rubric_item_key' => 'item_a',
            'weight_override' => 200,
        ]);

        $result = $this->merger->merge(
            collect([$global]),
            null,
            $version->rubricOverrides,
        );

        $itemA = collect($result->items)->firstWhere('key', 'item_a');
        $itemB = collect($result->items)->firstWhere('key', 'item_b');

        $this->assertSame(200, $itemA->weight);
        $this->assertSame(50, $itemB->weight);
    }

    public function test_inactive_global_rubric_is_excluded(): void
    {
        $active = EvaluationRubricFactory::new()->create(['name' => 'Active', 'is_active' => true]);
        $active->items()->create(['key' => 'active_key', 'title' => 'Active', 'weight' => 100]);

        $inactive = EvaluationRubricFactory::new()->inactive()->create(['name' => 'Inactive']);
        $inactive->items()->create(['key' => 'inactive_key', 'title' => 'Inactive', 'weight' => 100]);

        $result = $this->merger->merge(
            collect([$active, $inactive]),
            null,
            collect(),
        );

        $this->assertCount(1, $result->items);
        $this->assertSame('active_key', $result->items[0]->key);
    }
}
