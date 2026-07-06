<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\PersonaObjection;
use App\Models\PersonaVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HqPersonaObjectionTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $sales;
    private array $objectionPayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->sales = User::factory()->sales()->create();

        $this->objectionPayload = [
            'key' => 'CICILAN_BERAT',
            'title' => 'Cicilan Terlalu Berat',
            'context' => 'Konsumen khawatir cicilan KPR melebihi kemampuan finansial bulanan.',
            'visibility' => 'VISIBLE',
            'severity' => '75',
            'emotional_importance' => '75',
            'persistence' => '50',
            'trigger_conditions_text' => 'sales menyebut harga, sales menyebut nominal cicilan',
            'disclosure_conditions_text' => 'trust > 40, sales bertanya tentang kemampuan finansial',
            'resolution_conditions_text' => 'acknowledge kekhawatiran, klarifikasi KPR bersubsidi',
            'is_resolvable' => '1',
        ];
    }

    public function test_super_admin_can_create_persona_with_objection(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'OBJ_01',
                'name' => 'Objection Test',
                'description' => 'Testing objections',
                'objections' => [$this->objectionPayload],
            ]);

        $response->assertSessionHas('success');

        $persona = Persona::where('code', 'OBJ_01')->first();
        $version = $persona->currentVersion;

        $this->assertCount(1, $version->objections);

        $objection = $version->objections->first();
        $this->assertEquals('CICILAN_BERAT', $objection->key);
        $this->assertEquals('Cicilan Terlalu Berat', $objection->title);
        $this->assertEquals('Konsumen khawatir cicilan KPR melebihi kemampuan finansial bulanan.', $objection->context);
        $this->assertEquals('VISIBLE', $objection->visibility);
        $this->assertEquals(75, $objection->severity);
        $this->assertEquals(75, $objection->emotional_importance);
        $this->assertEquals(50, $objection->persistence);
        $this->assertTrue($objection->is_resolvable);
        $this->assertTrue($objection->is_active);
        $this->assertEquals('sales menyebut harga', $objection->trigger_conditions_json[0]);
        $this->assertEquals('trust > 40', $objection->disclosure_conditions_json[0]);
        $this->assertEquals('acknowledge kekhawatiran', $objection->resolution_conditions_json[0]);
    }

    public function test_super_admin_can_create_hidden_objection(): void
    {
        $payload = $this->objectionPayload;
        $payload['visibility'] = 'HIDDEN';

        $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'HIDDEN_OBJ',
                'name' => 'Hidden Objection',
                'objections' => [$payload],
            ]);

        $persona = Persona::where('code', 'HIDDEN_OBJ')->first();
        $objection = $persona->currentVersion->objections->first();

        $this->assertEquals('HIDDEN', $objection->visibility);
    }

    public function test_super_admin_can_update_objection(): void
    {
        $persona = Persona::factory()->create(['created_by' => $this->superAdmin->id]);
        $v1 = PersonaVersion::create([
            'persona_id' => $persona->id,
            'version_number' => 1,
            'identity_json' => [],
            'created_by' => $this->superAdmin->id,
            'created_at' => now(),
        ]);
        $persona->update(['current_version_id' => $v1->id]);

        $v1->objections()->create([
            'key' => 'CICILAN_BERAT',
            'title' => 'Cicilan Terlalu Berat',
            'context' => 'Versi asli',
            'visibility' => 'VISIBLE',
            'severity' => 50,
            'emotional_importance' => 50,
            'persistence' => 50,
            'is_resolvable' => true,
            'is_active' => true,
        ]);

        $updatedPayload = $this->objectionPayload;
        $updatedPayload['key'] = 'CICILAN_BERAT';
        $updatedPayload['title'] = 'Cicilan Berat (Diperbarui)';
        $updatedPayload['context'] = 'Versi baru setelah update';
        $updatedPayload['severity'] = '100';

        $this->actingAs($this->superAdmin)
            ->put(route('hq.personas.update', $persona), [
                'code' => $persona->code,
                'name' => $persona->name,
                'description' => 'Updated',
                'objections' => [$updatedPayload],
            ]);

        $persona->refresh();
        $this->assertEquals(2, $persona->currentVersion->version_number);

        $objection = $persona->currentVersion->objections->first();
        $this->assertEquals('Cicilan Berat (Diperbarui)', $objection->title);
        $this->assertEquals('Versi baru setelah update', $objection->context);
        $this->assertEquals(100, $objection->severity);
    }

    public function test_super_admin_can_archive_objection(): void
    {
        $persona = Persona::factory()->create(['created_by' => $this->superAdmin->id]);
        $v1 = PersonaVersion::create([
            'persona_id' => $persona->id,
            'version_number' => 1,
            'identity_json' => [],
            'created_by' => $this->superAdmin->id,
            'created_at' => now(),
        ]);
        $persona->update(['current_version_id' => $v1->id]);

        $payload = $this->objectionPayload;
        $payload['is_archived'] = '1';

        $this->actingAs($this->superAdmin)
            ->put(route('hq.personas.update', $persona), [
                'code' => $persona->code,
                'name' => $persona->name,
                'description' => 'Archive objection',
                'objections' => [$payload],
            ]);

        $persona->refresh();
        $objection = $persona->currentVersion->objections->first();
        $this->assertFalse($objection->is_active);
    }

    public function test_archived_objection_is_not_active(): void
    {
        $persona = Persona::factory()->create(['created_by' => $this->superAdmin->id]);
        $v1 = PersonaVersion::create([
            'persona_id' => $persona->id,
            'version_number' => 1,
            'identity_json' => [],
            'created_by' => $this->superAdmin->id,
            'created_at' => now(),
        ]);
        $persona->update(['current_version_id' => $v1->id]);

        $v1->objections()->create([
            'key' => 'TEST',
            'title' => 'Test',
            'visibility' => 'VISIBLE',
            'severity' => 50,
            'emotional_importance' => 50,
            'persistence' => 50,
            'is_resolvable' => true,
            'is_active' => false,
        ]);

        $objection = $v1->objections->first();
        $this->assertFalse($objection->is_active);
    }

    public function test_sales_cannot_manage_objections(): void
    {
        $response = $this->actingAs($this->sales)
            ->post(route('hq.personas.store'), [
                'code' => 'SALES_OBJ',
                'name' => 'Sales Test',
                'objections' => [$this->objectionPayload],
            ]);

        $response->assertForbidden();

        $response = $this->actingAs($this->sales)
            ->get(route('hq.personas.create'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_manage_objections(): void
    {
        $response = $this->post(route('hq.personas.store'), [
            'code' => 'GUEST_OBJ',
            'name' => 'Guest Test',
            'objections' => [$this->objectionPayload],
        ]);

        $response->assertRedirect(route('login'));

        $response = $this->get(route('hq.personas.create'));
        $response->assertRedirect(route('login'));
    }

    public function test_objection_requires_key_and_title(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'INCOMPLETE',
                'name' => 'Incomplete',
                'objections' => [
                    ['title' => 'No Key'],
                ],
            ]);

        $response->assertSessionHas('success');

        $persona = Persona::where('code', 'INCOMPLETE')->first();
        $this->assertCount(0, $persona->currentVersion->objections);
    }

    public function test_visible_objection_visibility_stored_correctly(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'OBJ_VIS',
                'name' => 'Visible Obj',
                'objections' => [
                    [
                        'key' => 'VISIBLE_OBJ',
                        'title' => 'Visible',
                        'visibility' => 'VISIBLE',
                    ],
                ],
            ]);

        $persona = Persona::where('code', 'OBJ_VIS')->first();
        $objection = $persona->currentVersion->objections->first();

        $this->assertEquals('VISIBLE', $objection->visibility);
        $this->assertTrue($objection->is_active);
    }

    public function test_hidden_objection_visibility_stored_correctly(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'OBJ_HID',
                'name' => 'Hidden Obj',
                'objections' => [
                    [
                        'key' => 'HIDDEN_OBJ',
                        'title' => 'Hidden',
                        'visibility' => 'HIDDEN',
                    ],
                ],
            ]);

        $persona = Persona::where('code', 'OBJ_HID')->first();
        $objection = $persona->currentVersion->objections->first();

        $this->assertEquals('HIDDEN', $objection->visibility);
    }

    public function test_old_version_objections_unchanged_after_update(): void
    {
        $persona = Persona::factory()->create(['created_by' => $this->superAdmin->id]);
        $v1 = PersonaVersion::create([
            'persona_id' => $persona->id,
            'version_number' => 1,
            'identity_json' => [],
            'created_by' => $this->superAdmin->id,
            'created_at' => now(),
        ]);
        $persona->update(['current_version_id' => $v1->id]);

        $v1->objections()->create([
            'key' => 'ASLI',
            'title' => 'Versi Asli',
            'context' => 'Konteks asli',
            'visibility' => 'VISIBLE',
            'severity' => 50,
            'emotional_importance' => 50,
            'persistence' => 50,
            'is_resolvable' => true,
            'is_active' => true,
        ]);

        $updatedPayload = $this->objectionPayload;
        $updatedPayload['key'] = 'ASLI';
        $updatedPayload['title'] = 'Versi Berubah';
        $updatedPayload['context'] = 'Konteks berubah';

        $this->actingAs($this->superAdmin)
            ->put(route('hq.personas.update', $persona), [
                'code' => $persona->code,
                'name' => $persona->name,
                'description' => 'Updated',
                'objections' => [$updatedPayload],
            ]);

        $v1->refresh();

        $oldObjection = $v1->objections->first();
        $this->assertEquals('Versi Asli', $oldObjection->title);
        $this->assertEquals('Konteks asli', $oldObjection->context);
        $this->assertEquals(1, $v1->objections->count());
    }

    public function test_multiple_objections_can_be_stored(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'MULTI_OBJ',
                'name' => 'Multi Obj',
                'objections' => [
                    [
                        'key' => 'OBJ_1',
                        'title' => 'Objection 1',
                        'visibility' => 'VISIBLE',
                    ],
                    [
                        'key' => 'OBJ_2',
                        'title' => 'Objection 2',
                        'visibility' => 'HIDDEN',
                    ],
                    [
                        'key' => 'OBJ_3',
                        'title' => 'Objection 3',
                        'visibility' => 'VISIBLE',
                    ],
                ],
            ]);

        $persona = Persona::where('code', 'MULTI_OBJ')->first();
        $objections = $persona->currentVersion->objections;

        $this->assertCount(3, $objections);
        $this->assertEquals('OBJ_1', $objections[0]->key);
        $this->assertEquals('HIDDEN', $objections[1]->visibility);
    }
}
