<?php

namespace Tests\Unit;

use App\Services\Director\BehaviorTranslationResult;
use App\Services\Director\DirectorState;
use App\Services\Director\StateBand;
use App\Services\Director\StateToBehaviorTranslator;
use Tests\TestCase;

class StateToBehaviorTranslatorTest extends TestCase
{
    public function test_default_state_returns_moderate_or_low_bands(): void
    {
        $state = DirectorState::default();
        $translator = new StateToBehaviorTranslator;
        $result = $translator->translate($state);

        $this->assertSame(StateBand::MODERATE, $result->bands['trust']);
        $this->assertSame(StateBand::MODERATE, $result->bands['interest']);
        $this->assertSame(StateBand::VERY_LOW, $result->bands['confusion']);
        $this->assertSame(StateBand::LOW, $result->bands['anxiety']);
        $this->assertSame(StateBand::VERY_LOW, $result->bands['irritation']);
        $this->assertSame(StateBand::VERY_LOW, $result->bands['pressure_perception']);
        $this->assertSame(StateBand::MODERATE, $result->bands['engagement']);
    }

    public function test_low_trust_produces_kurang_percaya(): void
    {
        $state = new DirectorState(trust: 15);
        $translator = new StateToBehaviorTranslator;
        $result = $translator->translate($state);

        $this->assertSame(StateBand::VERY_LOW, $result->bands['trust']);
        $this->assertStringContainsString('tidak percaya', $result->qualitativeText);
    }

    public function test_high_irritation_produces_cukup_kesal(): void
    {
        $state = new DirectorState(irritation: 65);
        $translator = new StateToBehaviorTranslator;
        $result = $translator->translate($state);

        $this->assertSame(StateBand::HIGH, $result->bands['irritation']);
        $this->assertStringContainsString('cukup kesal', $result->qualitativeText);
    }

    public function test_very_high_engagement_produces_sangat_terlibat(): void
    {
        $state = new DirectorState(engagement: 85);
        $translator = new StateToBehaviorTranslator;
        $result = $translator->translate($state);

        $this->assertSame(StateBand::VERY_HIGH, $result->bands['engagement']);
        $this->assertStringContainsString('sangat terlibat', $result->qualitativeText);
    }

    public function test_mixed_state_produces_combined_text(): void
    {
        $state = new DirectorState(trust: 27, irritation: 68, interest: 72);
        $translator = new StateToBehaviorTranslator;
        $result = $translator->translate($state);

        $this->assertSame(StateBand::LOW, $result->bands['trust']);
        $this->assertSame(StateBand::HIGH, $result->bands['irritation']);
        $this->assertSame(StateBand::HIGH, $result->bands['interest']);

        $this->assertStringContainsString('kurang percaya', $result->qualitativeText);
        $this->assertStringContainsString('cukup kesal', $result->qualitativeText);
        $this->assertStringContainsString('tertarik', $result->qualitativeText);
    }

    public function test_all_extremes_produce_full_text(): void
    {
        $state = new DirectorState(
            trust: 95,
            interest: 5,
            confusion: 90,
            anxiety: 85,
            irritation: 80,
            pressurePerception: 75,
            engagement: 10,
        );
        $translator = new StateToBehaviorTranslator;
        $result = $translator->translate($state);

        $this->assertSame(StateBand::VERY_HIGH, $result->bands['trust']);
        $this->assertSame(StateBand::VERY_LOW, $result->bands['interest']);
        $this->assertSame(StateBand::VERY_HIGH, $result->bands['confusion']);
        $this->assertSame(StateBand::VERY_HIGH, $result->bands['anxiety']);
        $this->assertSame(StateBand::HIGH, $result->bands['irritation']);
        $this->assertSame(StateBand::HIGH, $result->bands['pressure_perception']);
        $this->assertSame(StateBand::VERY_LOW, $result->bands['engagement']);

        $this->assertStringContainsString('sangat percaya', $result->qualitativeText);
        $this->assertStringContainsString('tidak tertarik', $result->qualitativeText);
        $this->assertStringContainsString('sangat bingung', $result->qualitativeText);
    }

    public function test_output_is_deterministic(): void
    {
        $state = new DirectorState(trust: 33, irritation: 55, interest: 78);
        $translator = new StateToBehaviorTranslator;

        $result1 = $translator->translate($state);
        $result2 = $translator->translate($state);

        $this->assertSame($result1->qualitativeText, $result2->qualitativeText);
        $this->assertSame($result1->toArray(), $result2->toArray());
    }

    public function test_has_no_ai_dependency(): void
    {
        $state = new DirectorState(
            trust: 20,
            interest: 80,
            confusion: 50,
            anxiety: 40,
            irritation: 30,
            pressurePerception: 10,
            engagement: 70,
        );
        $translator = new StateToBehaviorTranslator;
        $result = $translator->translate($state);

        $this->assertInstanceOf(BehaviorTranslationResult::class, $result);
        $this->assertIsString($result->qualitativeText);
        $this->assertNotEmpty($result->qualitativeText);
    }

    public function test_director_note_suggestion_low_trust(): void
    {
        $state = new DirectorState(trust: 15);
        $translator = new StateToBehaviorTranslator;
        $result = $translator->translate($state);

        $this->assertStringContainsString('Kepercayaan masih rendah', $result->directorNoteSuggestion);
    }

    public function test_director_note_suggestion_high_irritation(): void
    {
        $state = new DirectorState(irritation: 70);
        $translator = new StateToBehaviorTranslator;
        $result = $translator->translate($state);

        $this->assertStringContainsString('Kekesalan cukup tinggi', $result->directorNoteSuggestion);
    }

    public function test_director_note_suggestion_low_engagement(): void
    {
        $state = new DirectorState(engagement: 20);
        $translator = new StateToBehaviorTranslator;
        $result = $translator->translate($state);

        $this->assertStringContainsString('Keterlibatan menurun', $result->directorNoteSuggestion);
    }

    public function test_band_from_value_edge_cases(): void
    {
        $this->assertSame(StateBand::VERY_LOW, StateBand::fromValue(0));
        $this->assertSame(StateBand::VERY_LOW, StateBand::fromValue(20));
        $this->assertSame(StateBand::LOW, StateBand::fromValue(21));
        $this->assertSame(StateBand::LOW, StateBand::fromValue(40));
        $this->assertSame(StateBand::MODERATE, StateBand::fromValue(41));
        $this->assertSame(StateBand::MODERATE, StateBand::fromValue(60));
        $this->assertSame(StateBand::HIGH, StateBand::fromValue(61));
        $this->assertSame(StateBand::HIGH, StateBand::fromValue(80));
        $this->assertSame(StateBand::VERY_HIGH, StateBand::fromValue(81));
        $this->assertSame(StateBand::VERY_HIGH, StateBand::fromValue(100));
    }

    public function test_result_to_array(): void
    {
        $state = new DirectorState(trust: 20, interest: 70);
        $translator = new StateToBehaviorTranslator;
        $result = $translator->translate($state);

        $array = $result->toArray();

        $this->assertArrayHasKey('bands', $array);
        $this->assertArrayHasKey('qualitative_text', $array);
        $this->assertArrayHasKey('director_note_suggestion', $array);
        $this->assertSame('very_low', $array['bands']['trust']);
        $this->assertSame('high', $array['bands']['interest']);
    }
}
