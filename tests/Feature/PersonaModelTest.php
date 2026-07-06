<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\PersonaVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonaModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_persona_can_be_created(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $persona = Persona::create([
            'code' => 'BUDI_01',
            'name' => 'Budi Santoso',
            'status' => Persona::STATUS_ACTIVE,
            'created_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('personas', [
            'code' => 'BUDI_01',
            'name' => 'Budi Santoso',
            'status' => Persona::STATUS_ACTIVE,
        ]);

        $this->assertTrue($persona->isActive());
        $this->assertFalse($persona->isArchived());
    }

    public function test_persona_has_version_on_creation(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $persona = Persona::factory()->create(['created_by' => $admin->id]);

        $version = PersonaVersion::create([
            'persona_id' => $persona->id,
            'version_number' => 1,
            'public_profile_text' => 'Test profile',
            'identity_json' => ['age' => 30],
            'created_by' => $admin->id,
            'created_at' => now(),
        ]);

        $persona->update(['current_version_id' => $version->id]);
        $persona->fresh();

        $this->assertTrue($persona->currentVersion->is($version));
        $this->assertCount(1, $persona->versions);
    }

    public function test_persona_status_helpers(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $active = Persona::factory()->create(['created_by' => $admin->id]);
        $this->assertTrue($active->isActive());
        $this->assertFalse($active->isArchived());

        $archived = Persona::factory()->archived()->create(['created_by' => $admin->id]);
        $this->assertTrue($archived->isArchived());
        $this->assertFalse($archived->isActive());
    }

    public function test_persona_archive_method(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $persona = Persona::factory()->create(['created_by' => $admin->id]);

        $persona->archive();

        $this->assertTrue($persona->isArchived());
        $this->assertDatabaseHas('personas', [
            'id' => $persona->id,
            'status' => Persona::STATUS_ARCHIVED,
        ]);
    }

    public function test_persona_duplicate_creates_new_persona_with_cloned_version(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $persona = Persona::factory()->create(['created_by' => $admin->id]);
        $version = PersonaVersion::create([
            'persona_id' => $persona->id,
            'version_number' => 1,
            'public_profile_text' => 'Original profile',
            'identity_json' => ['age' => 30, 'occupation' => 'Swasta'],
            'created_by' => $admin->id,
            'created_at' => now(),
        ]);
        $persona->update(['current_version_id' => $version->id]);

        $clone = $persona->duplicate($admin);

        $this->assertNotEquals($persona->id, $clone->id);
        $this->assertStringStartsWith($persona->code, $clone->code);
        $this->assertStringContainsString('(Salinan)', $clone->name);
        $this->assertTrue($clone->isActive());
        $this->assertNotNull($clone->currentVersion);
        $this->assertEquals(1, $clone->currentVersion->version_number);
        $this->assertEquals('Original profile', $clone->currentVersion->public_profile_text);
    }

    public function test_persona_database_default_status_is_active(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $persona = Persona::create([
            'code' => 'DEFAULT_TEST',
            'name' => 'Test',
            'created_by' => $admin->id,
        ]);

        $this->assertEquals(Persona::STATUS_ACTIVE, $persona->fresh()->status);
    }
}
