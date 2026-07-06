<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\PersonaVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HqPersonaBuilderTest extends TestCase
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

    public function test_creating_persona_stores_identity_section(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'BUDI_01',
                'name' => 'Budi Santoso',
                'identity' => [
                    'age' => 30,
                    'gender' => 'Pria',
                    'marital_status' => 'Menikah',
                    'children' => 2,
                    'occupation' => 'Karyawan Swasta',
                    'employment_type' => 'Karyawan Swasta',
                    'income_range' => '4-6 Juta',
                ],
            ]);

        $response->assertSessionHas('success');

        $persona = Persona::where('code', 'BUDI_01')->first();
        $version = $persona->currentVersion;

        $this->assertEquals(30, $version->identity_json['age']);
        $this->assertEquals('Pria', $version->identity_json['gender']);
        $this->assertEquals('Menikah', $version->identity_json['marital_status']);
        $this->assertEquals(2, $version->identity_json['children']);
        $this->assertArrayNotHasKey('spouse_occupation', $version->identity_json);
    }

    public function test_creating_persona_stores_all_sections(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'BUDI_02',
                'name' => 'Budi Dua',
                'description' => 'Testing semua section',
                'identity' => ['age' => 28, 'gender' => 'Pria'],
                'housing_context' => [
                    'current_housing_situation' => 'Kontrak',
                    'target_location' => 'Jakarta',
                ],
                'knowledge_beliefs' => [
                    'kpr_knowledge' => 'Sedikit Tahu',
                    'misconceptions_text' => 'KPR butuh DP besar, rumah subsidi kecil',
                ],
                'personality' => [
                    'friendliness' => 50,
                    'skepticism' => 75,
                    'patience' => 25,
                ],
                'human_behavior_traits' => [
                    'dominance' => 50,
                    'flirtatiousness' => 25,
                ],
                'communication_style' => [
                    'formality' => 'Cenderung Santai',
                    'directness' => 'Langsung',
                ],
                'initial_state' => [
                    'trust' => 30,
                    'interest' => 60,
                ],
                'state_sensitivity' => [
                    'trust_gain_rate' => '0.5',
                    'anxiety_sensitivity_topics_text' => 'cicilan, SLIK',
                ],
                'salience_overrides' => [
                    'primary_traits_text' => 'skeptis, finansial sensitif',
                ],
            ]);

        $response->assertSessionHas('success');

        $persona = Persona::where('code', 'BUDI_02')->first();
        $version = $persona->currentVersion;

        $this->assertEquals('Testing semua section', $version->public_profile_text);
        $this->assertEquals(28, $version->identity_json['age']);
        $this->assertEquals('Kontrak', $version->housing_context_json['current_housing_situation']);
        $this->assertEquals('Sedikit Tahu', $version->knowledge_beliefs_json['kpr_knowledge']);
        $this->assertEquals(['KPR butuh DP besar', 'rumah subsidi kecil'], $version->knowledge_beliefs_json['misconceptions']);
        $this->assertEquals(50, $version->personality_profile_json['friendliness']);
        $this->assertEquals(75, $version->personality_profile_json['skepticism']);
        $this->assertEquals(50, $version->human_behavior_traits_json['dominance']);
        $this->assertEquals('Cenderung Santai', $version->communication_style_json['formality']);
        $this->assertEquals(30, $version->initial_dynamic_state_json['trust']);
        $this->assertEquals('0.5', $version->state_sensitivity_json['trust_gain_rate']);
        $this->assertEquals(['cicilan', 'SLIK'], $version->state_sensitivity_json['anxiety_sensitivity_topics']);
        $this->assertEquals(['skeptis', 'finansial sensitif'], $version->salience_overrides_json['primary_traits']);
    }

    public function test_updating_persona_creates_new_version_with_updated_sections(): void
    {
        $persona = Persona::factory()->create(['created_by' => $this->superAdmin->id]);
        $v1 = PersonaVersion::create([
            'persona_id' => $persona->id,
            'version_number' => 1,
            'public_profile_text' => 'Versi 1',
            'identity_json' => ['age' => 25, 'gender' => 'Pria'],
            'personality_profile_json' => ['friendliness' => 50],
            'created_by' => $this->superAdmin->id,
            'created_at' => now(),
        ]);
        $persona->update(['current_version_id' => $v1->id]);

        $this->actingAs($this->superAdmin)
            ->put(route('hq.personas.update', $persona), [
                'code' => $persona->code,
                'name' => $persona->name,
                'description' => 'Versi 2 dengan perubahan',
                'identity' => [
                    'age' => 30,
                    'gender' => 'Pria',
                    'occupation' => 'Wirausaha',
                ],
                'personality' => [
                    'friendliness' => 75,
                    'skepticism' => 50,
                ],
            ]);

        $persona->refresh();
        $this->assertEquals(2, $persona->currentVersion->version_number);
        $this->assertEquals('Versi 2 dengan perubahan', $persona->currentVersion->public_profile_text);

        $this->assertEquals(30, $persona->currentVersion->identity_json['age']);
        $this->assertEquals('Wirausaha', $persona->currentVersion->identity_json['occupation']);
        $this->assertEquals(75, $persona->currentVersion->personality_profile_json['friendliness']);
        $this->assertEquals(50, $persona->currentVersion->personality_profile_json['skepticism']);
    }

    public function test_old_persona_version_remains_unchanged_after_update(): void
    {
        $persona = Persona::factory()->create(['created_by' => $this->superAdmin->id]);
        $v1 = PersonaVersion::create([
            'persona_id' => $persona->id,
            'version_number' => 1,
            'public_profile_text' => 'Versi asli',
            'identity_json' => ['age' => 25, 'occupation' => 'Karyawan'],
            'personality_profile_json' => ['friendliness' => 50],
            'created_by' => $this->superAdmin->id,
            'created_at' => now(),
        ]);
        $persona->update(['current_version_id' => $v1->id]);

        $this->actingAs($this->superAdmin)
            ->put(route('hq.personas.update', $persona), [
                'code' => $persona->code,
                'name' => $persona->name,
                'description' => 'Versi baru',
                'identity' => [
                    'age' => 30,
                    'occupation' => 'Wirausaha',
                ],
                'personality' => [
                    'friendliness' => 75,
                ],
            ]);

        $v1->refresh();

        $this->assertEquals('Versi asli', $v1->public_profile_text);
        $this->assertEquals(25, $v1->identity_json['age']);
        $this->assertEquals('Karyawan', $v1->identity_json['occupation']);
        $this->assertEquals(50, $v1->personality_profile_json['friendliness']);
    }

    public function test_current_version_id_points_to_latest_version(): void
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

        $this->assertEquals($v1->id, $persona->fresh()->current_version_id);

        $this->actingAs($this->superAdmin)
            ->put(route('hq.personas.update', $persona), [
                'code' => $persona->code,
                'name' => $persona->name,
            ]);

        $persona->refresh();
        $v2 = $persona->currentVersion;

        $this->assertEquals(2, $v2->version_number);
        $this->assertEquals($v2->id, $persona->current_version_id);
        $this->assertNotEquals($v1->id, $persona->current_version_id);
    }

    public function test_sales_cannot_access_builder(): void
    {
        $response = $this->actingAs($this->sales)
            ->get(route('hq.personas.create'));

        $response->assertForbidden();

        $response = $this->actingAs($this->sales)
            ->post(route('hq.personas.store'), [
                'code' => 'TEST',
                'name' => 'Test',
            ]);

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_builder(): void
    {
        $response = $this->get(route('hq.personas.create'));
        $response->assertRedirect(route('login'));

        $response = $this->post(route('hq.personas.store'), [
            'code' => 'TEST',
            'name' => 'Test',
        ]);
        $response->assertRedirect(route('login'));
    }

    public function test_identity_validation_works(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'VALID_01',
                'name' => 'Valid',
                'identity' => [
                    'age' => 15,
                ],
            ]);

        $response->assertSessionHasErrors('identity.age');

        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'VALID_02',
                'name' => 'Valid',
                'identity' => [
                    'age' => 200,
                ],
            ]);

        $response->assertSessionHasErrors('identity.age');
    }

    public function test_personality_validation_accepts_valid_values(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'PERS_01',
                'name' => 'Personality Test',
                'personality' => [
                    'friendliness' => 999,
                ],
            ]);

        $response->assertSessionHasErrors('personality.friendliness');
    }

    public function test_archived_persona_cannot_be_edited(): void
    {
        $persona = Persona::factory()->archived()->create(['created_by' => $this->superAdmin->id]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.personas.edit', $persona));

        $response->assertForbidden();

        $response = $this->actingAs($this->superAdmin)
            ->put(route('hq.personas.update', $persona), [
                'code' => 'NEW_CODE',
                'name' => 'New Name',
            ]);

        $response->assertForbidden();
    }

    public function test_archived_persona_cannot_be_archived_again(): void
    {
        $persona = Persona::factory()->archived()->create(['created_by' => $this->superAdmin->id]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.archive', $persona));

        $response->assertForbidden();
    }

    public function test_personality_defaults_to_empty_when_not_submitted(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'EMPTY_PERS',
                'name' => 'Empty Pers',
            ]);

        $response->assertSessionHas('success');

        $persona = Persona::where('code', 'EMPTY_PERS')->first();
        $version = $persona->currentVersion;

        $this->assertEquals([], $version->personality_profile_json);
        $this->assertEquals([], $version->human_behavior_traits_json);
        $this->assertEquals([], $version->initial_dynamic_state_json);
    }
}
