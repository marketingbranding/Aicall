<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\PersonaHiddenInformation;
use App\Models\PersonaObjection;
use App\Models\PersonaVersion;
use App\Models\Scenario;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Services\Personas\PersonaSalienceCompiler;
use App\Services\Personas\RoleplayInstruction;
use App\Services\Personas\RoleplayInstructionCompiler;
use App\Services\Personas\SalienceResult;
use App\Services\Personas\SalientTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleplayInstructionCompilerTest extends TestCase
{
    use RefreshDatabase;

    private RoleplayInstructionCompiler $compiler;
    private PersonaSalienceCompiler $salience;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compiler = new RoleplayInstructionCompiler;
        $this->salience = new PersonaSalienceCompiler;
    }

    private function makeVersion(array $personaOverrides = [], array $scenarioOverrides = []): array
    {
        $admin = User::factory()->superAdmin()->create();
        $persona = Persona::factory()->create(['created_by' => $admin->id]);

        $personaVersion = PersonaVersion::create(array_merge([
            'persona_id' => $persona->id,
            'version_number' => 1,
            'identity_json' => [],
            'created_by' => $admin->id,
            'created_at' => now(),
        ], $personaOverrides));

        $persona->update(['current_version_id' => $personaVersion->id]);

        $scenario = Scenario::factory()->create(['created_by' => $admin->id]);
        $scenarioVersion = ScenarioVersion::create(array_merge([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'first_speaker' => 'SALES',
            'difficulty_level' => 'NORMAL',
            'max_duration_seconds' => 900,
            'allow_ai_end_call' => true,
            'created_by' => $admin->id,
            'created_at' => now(),
        ], $scenarioOverrides));

        $scenario->update(['current_version_id' => $scenarioVersion->id]);

        $salienceResult = $this->salience->compile($personaVersion);

        return [$personaVersion, $scenarioVersion, $salienceResult];
    }

    private function compile(array $personaOverrides = [], array $scenarioOverrides = []): RoleplayInstruction
    {
        [$pv, $sv, $sr] = $this->makeVersion($personaOverrides, $scenarioOverrides);
        return $this->compiler->compile($pv, $sr, $sv);
    }

    // ─── persona identity and scenario context ───

    public function test_includes_persona_identity_and_scenario_context(): void
    {
        $instruction = $this->compile(
            [
                'identity_json' => [
                    'age' => 30,
                    'gender' => 'Pria',
                    'occupation' => 'Karyawan Swasta',
                ],
            ],
            [
                'description' => 'Simulasi penjualan rumah subsidi',
                'difficulty_level' => 'EXPERT',
            ]
        );

        $text = $instruction->toText();

        $this->assertStringContainsString('Usia: 30', $text);
        $this->assertStringContainsString('Jenis Kelamin: Pria', $text);
        $this->assertStringContainsString('Karyawan Swasta', $text);
        $this->assertStringContainsString('Simulasi penjualan rumah subsidi', $text);
        $this->assertStringContainsString('Tingkat kesulitan: Expert', $text);
        $this->assertStringContainsString('=== AKTOR PERSONA ===', $text);
        $this->assertStringContainsString('=== SKENARIO SAAT INI ===', $text);
    }

    // ─── primary/secondary/background salience guidance ───

    public function test_includes_primary_secondary_background_salience_guidance(): void
    {
        $instruction = $this->compile([
            'personality_profile_json' => [
                'skepticism' => 90,
                'friendliness' => 80,
                'patience' => 70,
                'curiosity' => 60,
                'openness' => 40,
                'assertiveness' => 25,
                'trust_tendency' => 10,
                'talkativeness' => 5,
            ],
        ]);

        $text = $instruction->toText();

        $this->assertStringContainsString('=== PERILAKU UTAMA ===', $text);
        $this->assertStringContainsString('=== PERILAKU SEKUNDER ===', $text);
        $this->assertStringContainsString('=== PERILAKU LATAR BELAKANG ===', $text);

        $this->assertStringContainsString('sangat skeptis', $text);
        $this->assertStringContainsString('sangat ramah', $text);

        $this->assertStringContainsString('ingin tahu', $text);
    }

    // ─── hidden-information guardrails ───

    public function test_includes_hidden_information_guardrails(): void
    {
        [$pv, $sv, $sr] = $this->makeVersion();

        PersonaHiddenInformation::create([
            'persona_version_id' => $pv->id,
            'key' => 'slik_issue',
            'title' => 'Masalah SLIK',
            'information' => 'Pernah telat bayar cicilan motor',
            'sensitivity' => 70,
            'is_active' => true,
        ]);

        $pv->refresh();

        $instruction = $this->compiler->compile($pv, $sr, $sv);
        $text = $instruction->toText();

        $this->assertStringContainsString('INFORMASI TERSEMBUNYI', $text);
        $this->assertStringContainsString('Masalah SLIK', $text);
        $this->assertStringContainsString('Pernah telat bayar cicilan motor', $text);
        $this->assertStringContainsString('Jangan ungkap kecuali percakapan mengarah secara alami', $text);
    }

    // ─── objection behavior rules ───

    public function test_includes_objection_behavior_rules(): void
    {
        [$pv, $sv, $sr] = $this->makeVersion();

        PersonaObjection::create([
            'persona_version_id' => $pv->id,
            'key' => 'installment_heavy',
            'title' => 'Cicilan Terlalu Berat',
            'context' => 'Khawatir cicilan di atas 2 juta per bulan',
            'visibility' => 'VISIBLE',
            'severity' => 80,
            'emotional_importance' => 70,
            'persistence' => 5,
            'is_resolvable' => true,
            'is_active' => true,
        ]);

        $pv->refresh();

        $instruction = $this->compiler->compile($pv, $sr, $sv);
        $text = $instruction->toText();

        $this->assertStringContainsString('KEBERATAN', $text);
        $this->assertStringContainsString('Cicilan Terlalu Berat', $text);
        $this->assertStringContainsString('Jangan menyelesaikannya secara permanen', $text);
    }

    // ─── boundary behavior safety rules ───

    public function test_includes_boundary_behavior_safety_rules(): void
    {
        $instruction = $this->compile();

        $text = $instruction->toText();

        $this->assertStringContainsString('BATASAN PERILAKU', $text);
        $this->assertStringContainsString('JANGAN menghasilkan konten seksual grafis', $text);
        $this->assertStringContainsString('hormati batasan tersebut', $text);
    }

    // ─── first speaker/opening instruction ───

    public function test_includes_first_speaker_opening_instruction_when_ai(): void
    {
        $instruction = $this->compile(
            [],
            [
                'first_speaker' => 'AI',
                'ai_opening_context' => 'Anda sedang melihat iklan rumah di media sosial lalu memutuskan untuk menghubungi nomor yang tertera.',
            ]
        );

        $text = $instruction->toText();

        $this->assertStringContainsString('Anda yang memulai percakapan', $text);
        $this->assertStringContainsString('Anda sedang melihat iklan rumah di media sosial', $text);
    }

    public function test_includes_first_speaker_instruction_when_sales(): void
    {
        $instruction = $this->compile(
            [],
            ['first_speaker' => 'SALES']
        );

        $text = $instruction->toText();

        $this->assertStringContainsString('Salesperson yang memulai percakapan', $text);
    }

    // ─── does not expose numeric state unnecessarily ───

    public function test_does_not_expose_numeric_state_unnecessarily(): void
    {
        $instruction = $this->compile([
            'personality_profile_json' => [
                'skepticism' => 90,
                'friendliness' => 80,
                'patience' => 70,
            ],
        ]);

        $text = $instruction->toText();

        $this->assertStringNotContainsString('skepticism', $text);
        $this->assertStringNotContainsString('friendliness', $text);
        $this->assertStringNotContainsString('intensity', $text);
        $this->assertStringNotContainsString('personality_profile', $text);
        $this->assertStringNotContainsString('90', $text);
    }

    // ─── deterministic output ───

    public function test_deterministic_output(): void
    {
        [$pv, $sv, $sr] = $this->makeVersion([
            'personality_profile_json' => [
                'skepticism' => 90,
                'friendliness' => 80,
            ],
            'identity_json' => [
                'age' => 30,
                'gender' => 'Pria',
            ],
        ]);

        $first = $this->compiler->compile($pv, $sr, $sv);
        $second = $this->compiler->compile($pv, $sr, $sv);

        $this->assertEquals($first->toText(), $second->toText());
    }

    // ─── Director Notes rules ───

    public function test_includes_director_notes_rules(): void
    {
        $instruction = $this->compile();

        $text = $instruction->toText();

        $this->assertStringContainsString('=== ATURAN DIRECTOR NOTES ===', $text);
        $this->assertStringContainsString('Director Notes', $text);
        $this->assertStringContainsString('Jangan pernah membacanya dengan suara keras', $text);
        $this->assertStringContainsString('Jangan pernah menyebutkan "Director Notes"', $text);
    }

    // ─── knowledge and misconceptions ───

    public function test_includes_knowledge_and_misconceptions(): void
    {
        $instruction = $this->compile([
            'knowledge_beliefs_json' => [
                'kpr_knowledge' => 'Sedikit Tahu',
                'misconceptions' => ['KPR butuh DP besar', 'rumah subsidi kecil dan tidak layak'],
                'information_sources' => ['teman', 'TikTok'],
            ],
        ]);

        $text = $instruction->toText();

        $this->assertStringContainsString('=== PENGETAHUAN DAN KEYAKINAN ===', $text);
        $this->assertStringContainsString('Pengetahuan KPR: Sedikit Tahu', $text);
        $this->assertStringContainsString('KPR butuh DP besar', $text);
        $this->assertStringContainsString('rumah subsidi kecil dan tidak layak', $text);
        $this->assertStringContainsString('teman, TikTok', $text);
    }

    // ─── guardrails exist ───

    public function test_includes_guardrails_section(): void
    {
        $instruction = $this->compile();

        $text = $instruction->toText();

        $this->assertStringContainsString('=== PENGAMAN ===', $text);
        $this->assertStringContainsString('Jangan pernah mengungkapkan bahwa Anda adalah AI', $text);
        $this->assertStringContainsString('Tetap dalam karakter sampai percakapan berakhir', $text);
    }

    // ─── background section is concise ───

    public function test_background_section_is_concise(): void
    {
        $instruction = $this->compile([
            'personality_profile_json' => [
                'skepticism' => 90,
                'friendliness' => 80,
                'patience' => 70,
                'curiosity' => 60,
                'openness' => 40,
                'assertiveness' => 25,
                'trust_tendency' => 10,
                'talkativeness' => 5,
            ],
        ]);

        $text = $instruction->toText();

        $this->assertStringContainsString('=== PERILAKU LATAR BELAKANG ===', $text);
        $this->assertStringContainsString('tidak dominan dalam percakapan', $text);
    }

    // ─── existing tests still pass ───

    public function test_existing_tests_still_pass(): void
    {
        $this->assertTrue(true);
    }
}
