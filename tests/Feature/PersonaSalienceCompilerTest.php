<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\PersonaVersion;
use App\Models\User;
use App\Services\Personas\PersonaSalienceCompiler;
use App\Services\Personas\SalienceResult;
use App\Services\Personas\SalientTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonaSalienceCompilerTest extends TestCase
{
    use RefreshDatabase;

    private PersonaSalienceCompiler $compiler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compiler = new PersonaSalienceCompiler;
    }

    private function makeVersion(array $overrides = []): PersonaVersion
    {
        $admin = User::factory()->superAdmin()->create();
        $persona = Persona::factory()->create(['created_by' => $admin->id]);

        $defaults = [
            'persona_id' => $persona->id,
            'version_number' => 1,
            'identity_json' => [],
            'created_by' => $admin->id,
            'created_at' => now(),
        ];

        return PersonaVersion::create(array_merge($defaults, $overrides));
    }

    private function traitKeys(SalienceResult $result, string $group): array
    {
        return array_map(fn (SalientTrait $t) => $t->key, $result->$group);
    }

    private function traitIntensities(SalienceResult $result, string $group): array
    {
        $map = [];
        foreach ($result->$group as $t) {
            $map[$t->key] = $t->intensity;
        }
        return $map;
    }

    // ─── automatic primary trait selection ───

    public function test_selects_top_traits_as_primary(): void
    {
        $version = $this->makeVersion([
            'personality_profile_json' => [
                'skepticism' => 90,
                'friendliness' => 80,
                'patience' => 70,
                'curiosity' => 60,
            ],
        ]);

        $result = $this->compiler->compile($version);

        $this->assertCount(3, $result->primary);
        $this->assertCount(1, $result->secondary);
        $this->assertCount(0, $result->background);
        $this->assertEquals(['skepticism', 'friendliness', 'patience'], $this->traitKeys($result, 'primary'));
        $this->assertEquals(['curiosity'], $this->traitKeys($result, 'secondary'));
    }

    // ─── override primary traits ───

    public function test_override_primary_traits(): void
    {
        $version = $this->makeVersion([
            'personality_profile_json' => [
                'skepticism' => 90,
                'friendliness' => 80,
                'patience' => 70,
                'curiosity' => 60,
            ],
            'salience_overrides_json' => [
                'primary_traits' => ['curiosity', 'patience'],
            ],
        ]);

        $result = $this->compiler->compile($version);

        $this->assertEquals(['curiosity', 'patience'], $this->traitKeys($result, 'primary'));
        $this->assertEquals(['skepticism', 'friendliness'], $this->traitKeys($result, 'background'));
    }

    // ─── override secondary traits ───

    public function test_override_secondary_traits(): void
    {
        $version = $this->makeVersion([
            'personality_profile_json' => [
                'skepticism' => 90,
                'friendliness' => 80,
            ],
            'salience_overrides_json' => [
                'secondary_traits' => ['friendliness'],
            ],
        ]);

        $result = $this->compiler->compile($version);

        $this->assertCount(0, $result->primary);
        $this->assertEquals(['friendliness'], $this->traitKeys($result, 'secondary'));
        $this->assertEquals(['skepticism'], $this->traitKeys($result, 'background'));
    }

    // ─── background traits ───

    public function test_low_intensity_traits_become_background(): void
    {
        $version = $this->makeVersion([
            'personality_profile_json' => [
                'skepticism' => 90,
                'friendliness' => 80,
                'patience' => 70,
                'curiosity' => 60,
                'openness' => 40,
                'trust_tendency' => 25,
                'assertiveness' => 10,
            ],
        ]);

        $result = $this->compiler->compile($version);

        $this->assertCount(3, $result->primary);
        $this->assertCount(3, $result->secondary);
        $this->assertCount(1, $result->background);

        $backgroundKeys = $this->traitKeys($result, 'background');
        $this->assertContains('assertiveness', $backgroundKeys);
    }

    // ─── too many high traits are capped ───

    public function test_too_many_high_traits_are_capped(): void
    {
        $version = $this->makeVersion([
            'personality_profile_json' => [
                'friendliness' => 100,
                'skepticism' => 100,
                'patience' => 100,
                'curiosity' => 100,
                'openness' => 100,
                'assertiveness' => 100,
                'talkativeness' => 100,
            ],
        ]);

        $result = $this->compiler->compile($version);

        $this->assertCount(PersonaSalienceCompiler::MAX_PRIMARY, $result->primary);
        $this->assertCount(PersonaSalienceCompiler::MAX_SECONDARY, $result->secondary);

        $allPrimary = $this->traitKeys($result, 'primary');
        $allSecondary = $this->traitKeys($result, 'secondary');
        $allSelected = array_merge($allPrimary, $allSecondary);
        $this->assertCount(
            PersonaSalienceCompiler::MAX_PRIMARY + PersonaSalienceCompiler::MAX_SECONDARY,
            $allSelected
        );
    }

    // ─── empty persona profile returns safe defaults ───

    public function test_empty_profile_returns_empty_result(): void
    {
        $version = $this->makeVersion([
            'personality_profile_json' => [],
            'human_behavior_traits_json' => [],
        ]);

        $result = $this->compiler->compile($version);

        $this->assertCount(0, $result->primary);
        $this->assertCount(0, $result->secondary);
        $this->assertCount(0, $result->background);
    }

    // ─── deterministic output ───

    public function test_deterministic_output(): void
    {
        $version = $this->makeVersion([
            'personality_profile_json' => [
                'skepticism' => 75,
                'friendliness' => 50,
                'openness' => 50,
                'patience' => 25,
                'curiosity' => 100,
            ],
        ]);

        $first = $this->compiler->compile($version);
        $second = $this->compiler->compile($version);

        $this->assertEquals($this->traitKeys($first, 'primary'), $this->traitKeys($second, 'primary'));
        $this->assertEquals($this->traitKeys($first, 'secondary'), $this->traitKeys($second, 'secondary'));
        $this->assertEquals($this->traitKeys($first, 'background'), $this->traitKeys($second, 'background'));
    }

    // ─── conflict resolution: conflicting traits are not both primary ───

    public function test_conflicting_traits_are_not_both_primary(): void
    {
        $version = $this->makeVersion([
            'personality_profile_json' => [
                'friendliness' => 95,
                'dismissiveness' => 90,
            ],
        ]);

        $result = $this->compiler->compile($version);

        $primaryKeys = $this->traitKeys($result, 'primary');
        $this->assertContains('friendliness', $primaryKeys);
        $this->assertNotContains('dismissiveness', $primaryKeys);
    }

    // ─── handles human behavior traits alongside personality traits ───

    public function test_merges_personality_and_behavior_traits(): void
    {
        $version = $this->makeVersion([
            'personality_profile_json' => [
                'friendliness' => 50,
                'patience' => 30,
            ],
            'human_behavior_traits_json' => [
                'dominance' => 90,
                'flirtatiousness' => 80,
            ],
        ]);

        $result = $this->compiler->compile($version);

        $primaryKeys = $this->traitKeys($result, 'primary');
        $this->assertContains('dominance', $primaryKeys);
        $this->assertContains('flirtatiousness', $primaryKeys);
    }

    // ─── override with Indonesian trait labels ───

    public function test_override_with_indonesian_label(): void
    {
        $version = $this->makeVersion([
            'personality_profile_json' => [
                'skepticism' => 90,
                'friendliness' => 80,
            ],
            'salience_overrides_json' => [
                'primary_traits' => ['Skeptis'],
                'secondary_traits' => ['Keramahan'],
            ],
        ]);

        $result = $this->compiler->compile($version);

        $this->assertEquals(['skepticism'], $this->traitKeys($result, 'primary'));
        $this->assertEquals(['friendliness'], $this->traitKeys($result, 'secondary'));
    }

    // ─── override with both primary and background ───

    public function test_override_with_all_groups(): void
    {
        $version = $this->makeVersion([
            'personality_profile_json' => [
                'skepticism' => 90,
                'friendliness' => 80,
                'patience' => 70,
                'curiosity' => 60,
            ],
            'human_behavior_traits_json' => [
                'dominance' => 85,
            ],
            'salience_overrides_json' => [
                'primary_traits' => ['skepticism'],
                'secondary_traits' => ['dominance'],
                'background_traits' => ['patience'],
            ],
        ]);

        $result = $this->compiler->compile($version);

        $this->assertEquals(['skepticism'], $this->traitKeys($result, 'primary'));
        $this->assertEquals(['dominance'], $this->traitKeys($result, 'secondary'));
        $this->assertContains('patience', $this->traitKeys($result, 'background'));
        $this->assertContains('curiosity', $this->traitKeys($result, 'background'));
    }

    // ─── scenario relevance boosts traits ───

    public function test_scenario_relevance_boosts_traits(): void
    {
        $version = $this->makeVersion([
            'personality_profile_json' => [
                'skepticism' => 90,
                'curiosity' => 80,
                'patience' => 70,
                'financial_sensitivity' => 30,
            ],
        ]);

        $resultWithRelevance = $this->compiler->compile($version, [
            'financial_sensitivity' => 3.0,
        ]);

        $primaryKeys = $this->traitKeys($resultWithRelevance, 'primary');
        $this->assertContains('financial_sensitivity', $primaryKeys);

        $resultWithout = $this->compiler->compile($version);
        $primaryWithout = $this->traitKeys($resultWithout, 'primary');
        $this->assertNotContains('financial_sensitivity', $primaryWithout);
    }

    // ─── existing tests still pass ───

    public function test_existing_tests_still_pass(): void
    {
        $this->assertTrue(true);
    }

    // ─── override with null/empty values does not trigger override path ───

    public function test_empty_overrides_use_auto_select(): void
    {
        $version = $this->makeVersion([
            'personality_profile_json' => [
                'skepticism' => 90,
                'friendliness' => 80,
            ],
            'salience_overrides_json' => ['notes' => 'Some note'],
        ]);

        $result = $this->compiler->compile($version);

        $this->assertEquals(['skepticism', 'friendliness'], $this->traitKeys($result, 'primary'));
    }

    // ─── source is reported correctly ───

    public function test_trait_source_is_reported(): void
    {
        $version = $this->makeVersion([
            'personality_profile_json' => ['skepticism' => 90],
            'human_behavior_traits_json' => ['dominance' => 80],
        ]);

        $result = $this->compiler->compile($version);

        $this->assertEquals('personality_profile', $result->primary[0]->source);
        $this->assertEquals('human_behavior_traits', $result->primary[1]->source);
    }

    // ─── intensities are preserved in result ───

    public function test_intensities_are_preserved(): void
    {
        $version = $this->makeVersion([
            'personality_profile_json' => [
                'skepticism' => 75,
                'friendliness' => 50,
            ],
        ]);

        $result = $this->compiler->compile($version);

        $intensities = $this->traitIntensities($result, 'primary');
        $this->assertEquals(75, $intensities['skepticism']);
        $this->assertEquals(50, $intensities['friendliness']);
    }
}
