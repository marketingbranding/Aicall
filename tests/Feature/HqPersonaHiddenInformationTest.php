<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\PersonaHiddenInformation;
use App\Models\PersonaVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HqPersonaHiddenInformationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $sales;
    private array $hiddenInfoPayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->sales = User::factory()->sales()->create();

        $this->hiddenInfoPayload = [
            'key' => 'SLIK_MASALAH',
            'title' => 'Riwayat SLIK Bermasalah',
            'information' => 'Konsumen pernah memiliki kredit macet di bank lain dan khawatir tidak lolos KPR.',
            'sensitivity' => '75',
            'disclosure_difficulty' => '75',
            'direct_question_effectiveness' => '25',
            'trust_requirement' => '75',
            'relevant_topics_text' => 'SLIK, kredit macet, BI checking, KPR ditolak',
            'disclosure_conditions_text' => 'trust > 50, sales bertanya tentang riwayat kredit',
        ];
    }

    public function test_super_admin_can_create_hidden_information(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'HI_01',
                'name' => 'Hidden Info Test',
                'description' => 'Testing hidden information',
                'hidden_information' => [$this->hiddenInfoPayload],
            ]);

        $response->assertSessionHas('success');

        $persona = Persona::where('code', 'HI_01')->first();
        $version = $persona->currentVersion;

        $this->assertCount(1, $version->hiddenInformation);

        $info = $version->hiddenInformation->first();
        $this->assertEquals('SLIK_MASALAH', $info->key);
        $this->assertEquals('Riwayat SLIK Bermasalah', $info->title);
        $this->assertEquals('Konsumen pernah memiliki kredit macet di bank lain dan khawatir tidak lolos KPR.', $info->information);
        $this->assertEquals(75, $info->sensitivity);
        $this->assertEquals(75, $info->disclosure_difficulty);
        $this->assertEquals(25, $info->direct_question_effectiveness);
        $this->assertEquals(75, $info->trust_requirement);
        $this->assertTrue($info->is_active);
        $this->assertEquals('SLIK', $info->relevant_topics_json[0]);
        $this->assertEquals('kredit macet', $info->relevant_topics_json[1]);
        $this->assertEquals('trust > 50', $info->disclosure_conditions_json[0]);
    }

    public function test_super_admin_can_update_hidden_information(): void
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

        $v1->hiddenInformation()->create([
            'key' => 'SLIK_MASALAH',
            'title' => 'SLIK Versi Asli',
            'information' => 'Informasi asli',
            'sensitivity' => 50,
            'disclosure_difficulty' => 50,
            'direct_question_effectiveness' => 50,
            'trust_requirement' => 50,
            'is_active' => true,
        ]);

        $updatedPayload = $this->hiddenInfoPayload;
        $updatedPayload['key'] = 'SLIK_MASALAH';
        $updatedPayload['title'] = 'SLIK Versi Baru';
        $updatedPayload['information'] = 'Informasi setelah update';
        $updatedPayload['sensitivity'] = '100';

        $this->actingAs($this->superAdmin)
            ->put(route('hq.personas.update', $persona), [
                'code' => $persona->code,
                'name' => $persona->name,
                'description' => 'Updated',
                'hidden_information' => [$updatedPayload],
            ]);

        $persona->refresh();
        $this->assertEquals(2, $persona->currentVersion->version_number);

        $info = $persona->currentVersion->hiddenInformation->first();
        $this->assertEquals('SLIK Versi Baru', $info->title);
        $this->assertEquals('Informasi setelah update', $info->information);
        $this->assertEquals(100, $info->sensitivity);
    }

    public function test_old_version_hidden_information_unchanged(): void
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

        $v1->hiddenInformation()->create([
            'key' => 'SLIK_MASALAH',
            'title' => 'Versi Asli',
            'information' => 'Informasi asli',
            'sensitivity' => 50,
            'disclosure_difficulty' => 50,
            'direct_question_effectiveness' => 50,
            'trust_requirement' => 50,
            'is_active' => true,
        ]);

        $updatedPayload = $this->hiddenInfoPayload;
        $updatedPayload['key'] = 'SLIK_MASALAH';
        $updatedPayload['title'] = 'Versi Berubah';
        $updatedPayload['information'] = 'Informasi berubah';

        $this->actingAs($this->superAdmin)
            ->put(route('hq.personas.update', $persona), [
                'code' => $persona->code,
                'name' => $persona->name,
                'description' => 'Updated',
                'hidden_information' => [$updatedPayload],
            ]);

        $v1->refresh();

        $oldInfo = $v1->hiddenInformation->first();
        $this->assertEquals('Versi Asli', $oldInfo->title);
        $this->assertEquals('Informasi asli', $oldInfo->information);
        $this->assertEquals(1, $v1->hiddenInformation->count());
    }

    public function test_sales_cannot_manage_hidden_information(): void
    {
        $response = $this->actingAs($this->sales)
            ->post(route('hq.personas.store'), [
                'code' => 'SALES_HI',
                'name' => 'Sales Test',
                'hidden_information' => [$this->hiddenInfoPayload],
            ]);

        $response->assertForbidden();

        $response = $this->actingAs($this->sales)
            ->get(route('hq.personas.create'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_manage_hidden_information(): void
    {
        $response = $this->post(route('hq.personas.store'), [
            'code' => 'GUEST_HI',
            'name' => 'Guest Test',
            'hidden_information' => [$this->hiddenInfoPayload],
        ]);

        $response->assertRedirect(route('login'));

        $response = $this->get(route('hq.personas.create'));
        $response->assertRedirect(route('login'));
    }

    public function test_required_key_and_title_validated(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'INCOMPLETE_HI',
                'name' => 'Incomplete HI',
                'hidden_information' => [
                    ['title' => 'No Key'],
                ],
            ]);

        $response->assertSessionHas('success');

        $persona = Persona::where('code', 'INCOMPLETE_HI')->first();
        $this->assertCount(0, $persona->currentVersion->hiddenInformation);
    }

    public function test_relevant_topics_are_stored(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'TOPICS_HI',
                'name' => 'Topics Test',
                'hidden_information' => [
                    [
                        'key' => 'TOPICS',
                        'title' => 'Topics',
                        'relevant_topics_text' => 'topic A, topic B, topic C',
                    ],
                ],
            ]);

        $persona = Persona::where('code', 'TOPICS_HI')->first();
        $info = $persona->currentVersion->hiddenInformation->first();

        $this->assertCount(3, $info->relevant_topics_json);
        $this->assertEquals('topic A', $info->relevant_topics_json[0]);
        $this->assertEquals('topic B', $info->relevant_topics_json[1]);
        $this->assertEquals('topic C', $info->relevant_topics_json[2]);
    }

    public function test_disclosure_conditions_are_stored(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'COND_HI',
                'name' => 'Conditions Test',
                'hidden_information' => [
                    [
                        'key' => 'CONDITIONS',
                        'title' => 'Conditions',
                        'disclosure_conditions_text' => 'trust > 50, relevant topic asked, sales follow-up',
                    ],
                ],
            ]);

        $persona = Persona::where('code', 'COND_HI')->first();
        $info = $persona->currentVersion->hiddenInformation->first();

        $this->assertCount(3, $info->disclosure_conditions_json);
        $this->assertEquals('trust > 50', $info->disclosure_conditions_json[0]);
    }

    public function test_archived_hidden_information_is_not_active(): void
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

        $payload = $this->hiddenInfoPayload;
        $payload['is_archived'] = '1';

        $this->actingAs($this->superAdmin)
            ->put(route('hq.personas.update', $persona), [
                'code' => $persona->code,
                'name' => $persona->name,
                'description' => 'Archive HI',
                'hidden_information' => [$payload],
            ]);

        $persona->refresh();
        $info = $persona->currentVersion->hiddenInformation->first();
        $this->assertFalse($info->is_active);
    }

    public function test_multiple_hidden_information_items_can_be_stored(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'MULTI_HI',
                'name' => 'Multi HI',
                'hidden_information' => [
                    [
                        'key' => 'SLIK',
                        'title' => 'SLIK Issue',
                        'sensitivity' => '75',
                    ],
                    [
                        'key' => 'CICILAN_MOTOR',
                        'title' => 'Cicilan Motor',
                        'sensitivity' => '50',
                    ],
                    [
                        'key' => 'PENDAPATAN',
                        'title' => 'Pendapatan Terbatas',
                        'sensitivity' => '50',
                    ],
                ],
            ]);

        $persona = Persona::where('code', 'MULTI_HI')->first();
        $infoItems = $persona->currentVersion->hiddenInformation;

        $this->assertCount(3, $infoItems);
        $this->assertTrue($infoItems->contains('key', 'SLIK'));
        $this->assertTrue($infoItems->contains('key', 'CICILAN_MOTOR'));
    }

    public function test_hidden_information_with_only_key_and_title_is_valid(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'MINIMAL_HI',
                'name' => 'Minimal HI',
                'hidden_information' => [
                    [
                        'key' => 'MINIMAL',
                        'title' => 'Minimal Info',
                    ],
                ],
            ]);

        $persona = Persona::where('code', 'MINIMAL_HI')->first();
        $info = $persona->currentVersion->hiddenInformation->first();

        $this->assertEquals('MINIMAL', $info->key);
        $this->assertEquals('Minimal Info', $info->title);
        $this->assertEquals(50, $info->sensitivity);
        $this->assertEquals(50, $info->disclosure_difficulty);
        $this->assertEquals(50, $info->direct_question_effectiveness);
        $this->assertEquals(50, $info->trust_requirement);
        $this->assertTrue($info->is_active);
    }

    public function test_existing_tests_still_pass(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('hq.personas.store'), [
                'code' => 'REGRESSION',
                'name' => 'Regression Test',
                'hidden_information' => [
                    [
                        'key' => 'REGRESSION',
                        'title' => 'Regression',
                        'information' => 'Adding hidden info does not break persona creation',
                    ],
                ],
            ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('hq.personas.index'));

        $response->assertOk();
        $this->assertStringContainsString('Regression Test', $response->getContent());
    }
}
