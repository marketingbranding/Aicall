<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\PersonaVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HqPersonaCrudTest extends TestCase
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

    public function test_super_admin_can_view_persona_list(): void
    {
        $persona = Persona::factory()->create(['created_by' => $this->superAdmin->id]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.personas.index'));

        $response->assertOk();
        $response->assertSee($persona->name);
    }

    public function test_sales_cannot_view_persona_list(): void
    {
        $response = $this->actingAs($this->sales)
            ->get(route('hq.personas.index'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_persona_list(): void
    {
        $response = $this->get(route('hq.personas.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_create_form(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.personas.create'));

        $response->assertOk();
        $response->assertSee('Buat Persona Baru');
    }

    public function test_sales_cannot_view_create_form(): void
    {
        $response = $this->actingAs($this->sales)
            ->get(route('hq.personas.create'));

        $response->assertForbidden();
    }

    public function test_super_admin_can_create_persona(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'BUDI_01',
                'name' => 'Budi Santoso',
                'description' => 'Seorang karyawan swasta.',
            ]);

        $response->assertRedirect(route('hq.personas.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('personas', [
            'code' => 'BUDI_01',
            'name' => 'Budi Santoso',
            'status' => Persona::STATUS_ACTIVE,
        ]);

        $persona = Persona::where('code', 'BUDI_01')->first();
        $this->assertNotNull($persona->currentVersion);
        $this->assertEquals(1, $persona->currentVersion->version_number);
        $this->assertEquals('Seorang karyawan swasta.', $persona->currentVersion->public_profile_text);
        $this->assertEquals([], $persona->currentVersion->identity_json);
    }

    public function test_sales_cannot_create_persona(): void
    {
        $response = $this->actingAs($this->sales)
            ->post(route('hq.personas.store'), [
                'code' => 'BUDI_01',
                'name' => 'Budi Santoso',
            ]);

        $response->assertForbidden();
    }

    public function test_create_persona_requires_unique_code(): void
    {
        Persona::factory()->create([
            'code' => 'BUDI_01',
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'BUDI_01',
                'name' => 'Budi Lain',
            ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_super_admin_can_view_edit_form(): void
    {
        $persona = Persona::factory()->create(['created_by' => $this->superAdmin->id]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.personas.edit', $persona));

        $response->assertOk();
        $response->assertSee($persona->name);
        $response->assertSee('Edit Persona');
    }

    public function test_super_admin_can_update_persona(): void
    {
        $persona = Persona::factory()->create(['created_by' => $this->superAdmin->id]);
        $version = PersonaVersion::create([
            'persona_id' => $persona->id,
            'version_number' => 1,
            'public_profile_text' => 'Profil asli',
            'identity_json' => [],
            'created_by' => $this->superAdmin->id,
            'created_at' => now(),
        ]);
        $persona->update(['current_version_id' => $version->id]);

        $response = $this->actingAs($this->superAdmin)
            ->from(route('hq.personas.edit', $persona))
            ->put(route('hq.personas.update', $persona), [
                'code' => 'BUDI_UPDATED',
                'name' => 'Budi Update',
                'description' => 'Profil baru',
            ]);

        $response->assertRedirect(route('hq.personas.index'));
        $response->assertSessionHas('success');

        $persona->refresh();
        $this->assertEquals('BUDI_UPDATED', $persona->code);
        $this->assertEquals('Budi Update', $persona->name);

        $this->assertEquals(2, $persona->currentVersion->version_number);
        $this->assertEquals('Profil baru', $persona->currentVersion->public_profile_text);
    }

    public function test_sales_cannot_update_persona(): void
    {
        $persona = Persona::factory()->create();

        $response = $this->actingAs($this->sales)
            ->put(route('hq.personas.update', $persona), [
                'code' => 'NEW_CODE',
                'name' => 'New Name',
            ]);

        $response->assertForbidden();
    }

    public function test_super_admin_can_archive_persona(): void
    {
        $persona = Persona::factory()->create(['created_by' => $this->superAdmin->id]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.archive', $persona));

        $response->assertRedirect(route('hq.personas.index'));
        $response->assertSessionHas('success');

        $this->assertTrue($persona->fresh()->isArchived());
    }

    public function test_sales_cannot_archive_persona(): void
    {
        $persona = Persona::factory()->create();

        $response = $this->actingAs($this->sales)
            ->post(route('hq.personas.archive', $persona));

        $response->assertForbidden();
    }

    public function test_super_admin_can_duplicate_persona(): void
    {
        $persona = Persona::factory()->create(['created_by' => $this->superAdmin->id]);
        $version = PersonaVersion::create([
            'persona_id' => $persona->id,
            'version_number' => 1,
            'public_profile_text' => 'Profil asli',
            'identity_json' => ['age' => 25],
            'created_by' => $this->superAdmin->id,
            'created_at' => now(),
        ]);
        $persona->update(['current_version_id' => $version->id]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.duplicate', $persona));

        $response->assertRedirect(route('hq.personas.index'));
        $response->assertSessionHas('success');

        $clones = Persona::where('name', 'LIKE', $persona->name . '%')
            ->where('id', '!=', $persona->id)
            ->get();

        $this->assertCount(1, $clones);
        $this->assertTrue($clones->first()->isActive());
        $this->assertNotNull($clones->first()->currentVersion);
    }

    public function test_archived_persona_appears_in_list(): void
    {
        $active = Persona::factory()->create(['created_by' => $this->superAdmin->id]);
        $archived = Persona::factory()->archived()->create(['created_by' => $this->superAdmin->id]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.personas.index'));

        $response->assertSee($active->name);
        $response->assertSee($archived->name);
        $response->assertSee('Diarsipkan');
    }

    public function test_empty_state_when_no_personas(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.personas.index'));

        $response->assertOk();
        $response->assertSee('Belum ada persona');
    }

    public function test_update_persona_creates_new_version(): void
    {
        $persona = Persona::factory()->create(['created_by' => $this->superAdmin->id]);
        $v1 = PersonaVersion::create([
            'persona_id' => $persona->id,
            'version_number' => 1,
            'public_profile_text' => 'Versi 1',
            'identity_json' => [],
            'created_by' => $this->superAdmin->id,
            'created_at' => now(),
        ]);
        $persona->update(['current_version_id' => $v1->id]);

        $this->actingAs($this->superAdmin)
            ->put(route('hq.personas.update', $persona), [
                'code' => $persona->code,
                'name' => $persona->name,
                'description' => 'Versi 2',
            ]);

        $this->assertEquals(2, $persona->fresh()->currentVersion->version_number);
        $this->assertDatabaseHas('persona_versions', [
            'persona_id' => $persona->id,
            'version_number' => 2,
        ]);
        $this->assertDatabaseHas('persona_versions', [
            'persona_id' => $persona->id,
            'version_number' => 1,
        ]);
    }
}
