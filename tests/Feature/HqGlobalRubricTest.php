<?php

namespace Tests\Feature;

use App\Models\EvaluationRubric;
use App\Models\EvaluationRubricItem;
use App\Models\User;
use Database\Factories\EvaluationRubricFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HqGlobalRubricTest extends TestCase
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

    public function test_super_admin_can_view_global_rubric_list(): void
    {
        EvaluationRubricFactory::new()->create(['name' => 'Rubrik A']);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.global-rubrics.index'));

        $response->assertOk();
        $response->assertSee('Rubrik A');
    }

    public function test_sales_cannot_view_global_rubric_list(): void
    {
        $response = $this->actingAs($this->sales)
            ->get(route('hq.global-rubrics.index'));

        $response->assertForbidden();
    }

    public function test_super_admin_can_create_global_rubric(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.global-rubrics.store'), [
                'name' => 'Rubrik Test',
                'items' => [
                    [
                        'key' => 'greeting',
                        'title' => 'Salam Pembuka',
                        'weight' => 100,
                    ],
                ],
            ]);

        $response->assertRedirect(route('hq.global-rubrics.index'));

        $this->assertDatabaseHas('evaluation_rubrics', [
            'name' => 'Rubrik Test',
            'type' => 'GLOBAL',
            'is_active' => true,
            'version_number' => 1,
        ]);

        $this->assertDatabaseHas('evaluation_rubric_items', [
            'key' => 'greeting',
            'title' => 'Salam Pembuka',
            'weight' => 100,
        ]);
    }

    public function test_sales_cannot_create_global_rubric(): void
    {
        $response = $this->actingAs($this->sales)
            ->post(route('hq.global-rubrics.store'), [
                'name' => 'Rubrik Test',
            ]);

        $response->assertForbidden();
    }

    public function test_create_rubric_with_empty_items(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.global-rubrics.store'), [
                'name' => 'Rubrik Minimal',
            ]);

        $response->assertRedirect(route('hq.global-rubrics.index'));

        $this->assertDatabaseHas('evaluation_rubrics', [
            'name' => 'Rubrik Minimal',
        ]);
    }

    public function test_super_admin_can_edit_global_rubric(): void
    {
        $rubric = EvaluationRubricFactory::new()->create(['name' => 'Lama']);

        $response = $this->actingAs($this->superAdmin)
            ->put(route('hq.global-rubrics.update', $rubric), [
                'name' => 'Baru',
                'items' => [
                    [
                        'key' => 'closing',
                        'title' => 'Salam Penutup',
                        'weight' => 50,
                    ],
                ],
            ]);

        $response->assertRedirect(route('hq.global-rubrics.index'));

        $this->assertDatabaseHas('evaluation_rubrics', [
            'id' => $rubric->id,
            'name' => 'Baru',
        ]);

        $this->assertDatabaseHas('evaluation_rubric_items', [
            'evaluation_rubric_id' => $rubric->id,
            'key' => 'closing',
        ]);

        $this->assertDatabaseMissing('evaluation_rubric_items', [
            'key' => 'greeting',
        ]);
    }

    public function test_edit_rubric_replaces_items(): void
    {
        $rubric = EvaluationRubricFactory::new()->create();
        $rubric->items()->create([
            'key' => 'old_item',
            'title' => 'Item Lama',
            'weight' => 100,
        ]);

        $this->actingAs($this->superAdmin)
            ->put(route('hq.global-rubrics.update', $rubric), [
                'name' => 'Rubrik Update',
                'items' => [
                    [
                        'key' => 'new_item',
                        'title' => 'Item Baru',
                        'weight' => 200,
                    ],
                ],
            ]);

        $this->assertDatabaseMissing('evaluation_rubric_items', [
            'key' => 'old_item',
        ]);

        $this->assertDatabaseHas('evaluation_rubric_items', [
            'evaluation_rubric_id' => $rubric->id,
            'key' => 'new_item',
        ]);
    }

    public function test_sales_cannot_edit_global_rubric(): void
    {
        $rubric = EvaluationRubricFactory::new()->create();

        $response = $this->actingAs($this->sales)
            ->put(route('hq.global-rubrics.update', $rubric), [
                'name' => 'Hacked',
            ]);

        $response->assertForbidden();
    }

    public function test_super_admin_can_archive_global_rubric(): void
    {
        $rubric = EvaluationRubricFactory::new()->create();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.global-rubrics.archive', $rubric));

        $response->assertRedirect(route('hq.global-rubrics.index'));

        $this->assertDatabaseHas('evaluation_rubrics', [
            'id' => $rubric->id,
            'is_active' => false,
        ]);
    }

    public function test_sales_cannot_archive_global_rubric(): void
    {
        $rubric = EvaluationRubricFactory::new()->create();

        $response = $this->actingAs($this->sales)
            ->post(route('hq.global-rubrics.archive', $rubric));

        $response->assertForbidden();
    }

    public function test_archive_already_inactive_rubric_returns_forbidden(): void
    {
        $rubric = EvaluationRubricFactory::new()->inactive()->create();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.global-rubrics.archive', $rubric));

        $response->assertForbidden();
    }

    public function test_create_rubric_with_items_validation(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.global-rubrics.store'), [
                'name' => 'Rubrik Valid',
                'items' => [
                    [
                        'key' => 'item1',
                        'title' => 'Item Satu',
                        'weight' => 50,
                    ],
                    [
                        'key' => 'item2',
                        'title' => 'Item Dua',
                        'weight' => 50,
                    ],
                ],
            ]);

        $response->assertRedirect();

        $rubric = EvaluationRubric::where('name', 'Rubrik Valid')->first();

        $this->assertCount(2, $rubric->items);
    }

    public function test_create_rubric_with_disabled_item(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.global-rubrics.store'), [
                'name' => 'Rubrik Disabled',
                'items' => [
                    [
                        'key' => 'active_item',
                        'title' => 'Aktif',
                        'weight' => 100,
                    ],
                    [
                        'key' => 'disabled_item',
                        'title' => 'Nonaktif',
                        'weight' => 50,
                        'is_disabled' => true,
                    ],
                ],
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('evaluation_rubric_items', [
            'key' => 'active_item',
            'is_enabled' => true,
        ]);

        $this->assertDatabaseHas('evaluation_rubric_items', [
            'key' => 'disabled_item',
            'is_enabled' => false,
        ]);
    }

    public function test_guest_cannot_access_global_rubrics(): void
    {
        $response = $this->get(route('hq.global-rubrics.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_rubric_shows_in_index_after_create(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('hq.global-rubrics.store'), [
                'name' => 'Index Test Rubric',
                'items' => [
                    ['key' => 'k1', 'title' => 'K1', 'weight' => 100],
                ],
            ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.global-rubrics.index'));

        $response->assertSee('Index Test Rubric');
    }

    public function test_rubric_edit_page_shows_items(): void
    {
        $rubric = EvaluationRubricFactory::new()->create(['name' => 'Edit Test']);
        $rubric->items()->create([
            'key' => 'ek1',
            'title' => 'Edit Key 1',
            'weight' => 75,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.global-rubrics.edit', $rubric));

        $response->assertOk();
        $response->assertSee('Edit Key 1');
    }
}
