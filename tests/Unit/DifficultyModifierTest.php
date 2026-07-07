<?php

namespace Tests\Unit;

use App\Services\Director\DifficultyLevel;
use App\Services\Director\DifficultyModifier;
use App\Services\Director\DirectorState;
use App\Services\Director\RoleplayDirectorEngine;
use App\Services\Director\RoleplayEvent;
use App\Services\Director\RoleplayEventType;
use App\Services\Director\StateTransition;
use Tests\TestCase;

class DifficultyModifierTest extends TestCase
{
    public function test_beginner_defaults(): void
    {
        $m = DifficultyModifier::forLevel(DifficultyLevel::BEGINNER);

        $this->assertSame(1.5, $m->trustGainMultiplier);
        $this->assertSame(0.5, $m->trustLossMultiplier);
        $this->assertSame(20, $m->disclosureResistance);
        $this->assertSame(20, $m->objectionPersistence);
        $this->assertSame(20, $m->irritationSensitivity);
        $this->assertSame(80, $m->weakExplanationTolerance);
        $this->assertSame(20, $m->closingResistance);
        $this->assertSame(20, $m->boundaryPersistence);
    }

    public function test_normal_defaults(): void
    {
        $m = DifficultyModifier::forLevel(DifficultyLevel::NORMAL);

        $this->assertSame(1.0, $m->trustGainMultiplier);
        $this->assertSame(1.0, $m->trustLossMultiplier);
        $this->assertSame(50, $m->disclosureResistance);
        $this->assertSame(50, $m->objectionPersistence);
        $this->assertSame(50, $m->irritationSensitivity);
        $this->assertSame(50, $m->weakExplanationTolerance);
        $this->assertSame(50, $m->closingResistance);
        $this->assertSame(50, $m->boundaryPersistence);
    }

    public function test_difficult_defaults(): void
    {
        $m = DifficultyModifier::forLevel(DifficultyLevel::DIFFICULT);

        $this->assertSame(0.75, $m->trustGainMultiplier);
        $this->assertSame(1.25, $m->trustLossMultiplier);
        $this->assertSame(65, $m->disclosureResistance);
        $this->assertSame(65, $m->objectionPersistence);
        $this->assertSame(65, $m->irritationSensitivity);
        $this->assertSame(35, $m->weakExplanationTolerance);
        $this->assertSame(65, $m->closingResistance);
        $this->assertSame(65, $m->boundaryPersistence);
    }

    public function test_expert_defaults(): void
    {
        $m = DifficultyModifier::forLevel(DifficultyLevel::EXPERT);

        $this->assertSame(0.5, $m->trustGainMultiplier);
        $this->assertSame(1.5, $m->trustLossMultiplier);
        $this->assertSame(85, $m->disclosureResistance);
        $this->assertSame(85, $m->objectionPersistence);
        $this->assertSame(85, $m->irritationSensitivity);
        $this->assertSame(15, $m->weakExplanationTolerance);
        $this->assertSame(85, $m->closingResistance);
        $this->assertSame(85, $m->boundaryPersistence);
    }

    public function test_custom_modifiers_are_applied(): void
    {
        $m = DifficultyModifier::fromCustomConfig([
            'trust_gain_multiplier' => 2.0,
            'trust_loss_multiplier' => 0.3,
            'irritation_sensitivity' => 80,
            'boundary_persistence' => 90,
        ]);

        $this->assertSame(2.0, $m->trustGainMultiplier);
        $this->assertSame(0.3, $m->trustLossMultiplier);
        $this->assertSame(80, $m->irritationSensitivity);
        $this->assertSame(90, $m->boundaryPersistence);
    }

    public function test_custom_missing_values_fallback_to_normal_defaults(): void
    {
        $m = DifficultyModifier::fromCustomConfig([]);

        $this->assertSame(1.0, $m->trustGainMultiplier);
        $this->assertSame(1.0, $m->trustLossMultiplier);
        $this->assertSame(50, $m->disclosureResistance);
    }

    public function test_custom_partial_values_use_defaults_for_missing(): void
    {
        $m = DifficultyModifier::fromCustomConfig([
            'trust_gain_multiplier' => 0.3,
        ]);

        $this->assertSame(0.3, $m->trustGainMultiplier);
        $this->assertSame(1.0, $m->trustLossMultiplier);
        $this->assertSame(50, $m->disclosureResistance);
    }

    public function test_invalid_multiplier_clamped_low(): void
    {
        $m = new DifficultyModifier(trustGainMultiplier: -5.0);

        $this->assertSame(0.1, $m->trustGainMultiplier);
    }

    public function test_invalid_multiplier_clamped_high(): void
    {
        $m = new DifficultyModifier(trustLossMultiplier: 10.0);

        $this->assertSame(5.0, $m->trustLossMultiplier);
    }

    public function test_invalid_int_modifier_clamped_low(): void
    {
        $m = new DifficultyModifier(irritationSensitivity: -10);

        $this->assertSame(0, $m->irritationSensitivity);
    }

    public function test_invalid_int_modifier_clamped_high(): void
    {
        $m = new DifficultyModifier(closingResistance: 200);

        $this->assertSame(100, $m->closingResistance);
    }

    public function test_beginner_trust_gain_is_higher(): void
    {
        $beginner = DifficultyModifier::forLevel(DifficultyLevel::BEGINNER);
        $normal = DifficultyModifier::forLevel(DifficultyLevel::NORMAL);

        $base = new StateTransition(trustDelta: 4);

        $beginnerResult = $beginner->apply($base);
        $normalResult = $normal->apply($base);

        $this->assertSame(6, $beginnerResult->trustDelta);
        $this->assertSame(4, $normalResult->trustDelta);
    }

    public function test_expert_trust_gain_is_lower(): void
    {
        $expert = DifficultyModifier::forLevel(DifficultyLevel::EXPERT);
        $normal = DifficultyModifier::forLevel(DifficultyLevel::NORMAL);

        $base = new StateTransition(trustDelta: 4);

        $expertResult = $expert->apply($base);
        $normalResult = $normal->apply($base);

        $this->assertSame(2, $expertResult->trustDelta);
        $this->assertSame(4, $normalResult->trustDelta);
    }

    public function test_beginner_trust_loss_is_lower(): void
    {
        $beginner = DifficultyModifier::forLevel(DifficultyLevel::BEGINNER);
        $normal = DifficultyModifier::forLevel(DifficultyLevel::NORMAL);

        $base = new StateTransition(trustDelta: -5);

        $beginnerResult = $beginner->apply($base);
        $normalResult = $normal->apply($base);

        $this->assertSame(-3, $beginnerResult->trustDelta);
        $this->assertSame(-5, $normalResult->trustDelta);
    }

    public function test_expert_trust_loss_is_higher(): void
    {
        $expert = DifficultyModifier::forLevel(DifficultyLevel::EXPERT);
        $normal = DifficultyModifier::forLevel(DifficultyLevel::NORMAL);

        $base = new StateTransition(trustDelta: -5);

        $expertResult = $expert->apply($base);
        $normalResult = $normal->apply($base);

        $this->assertSame(-8, $expertResult->trustDelta);
        $this->assertSame(-5, $normalResult->trustDelta);
    }

    public function test_high_irritation_sensitivity_increases_irritation(): void
    {
        $high = new DifficultyModifier(irritationSensitivity: 80);
        $normal = new DifficultyModifier(irritationSensitivity: 50);

        $base = new StateTransition(irritationDelta: 5);

        $highResult = $high->apply($base);
        $normalResult = $normal->apply($base);

        $this->assertSame(8, $highResult->irritationDelta);
        $this->assertSame(5, $normalResult->irritationDelta);
    }

    public function test_low_irritation_sensitivity_decreases_irritation(): void
    {
        $low = new DifficultyModifier(irritationSensitivity: 20);
        $normal = new DifficultyModifier(irritationSensitivity: 50);

        $base = new StateTransition(irritationDelta: 5);

        $lowResult = $low->apply($base);
        $normalResult = $normal->apply($base);

        $this->assertSame(2, $lowResult->irritationDelta);
        $this->assertSame(5, $normalResult->irritationDelta);
    }

    public function test_high_closing_resistance_increases_pressure(): void
    {
        $high = new DifficultyModifier(closingResistance: 90);
        $base = new StateTransition(pressurePerceptionDelta: 8);

        $result = $high->apply($base);

        $this->assertSame(14, $result->pressurePerceptionDelta);
    }

    public function test_negative_deltas_not_scaled_by_sensitivity(): void
    {
        $high = new DifficultyModifier(irritationSensitivity: 80);
        $base = new StateTransition(irritationDelta: -3);

        $result = $high->apply($base);

        $this->assertSame(-3, $result->irritationDelta);
    }

    public function test_zero_delta_unchanged(): void
    {
        $m = DifficultyModifier::forLevel(DifficultyLevel::EXPERT);
        $base = new StateTransition;

        $result = $m->apply($base);

        $this->assertSame(0, $result->trustDelta);
        $this->assertSame(0, $result->irritationDelta);
    }

    public function test_deterministic_output(): void
    {
        $m1 = DifficultyModifier::forLevel(DifficultyLevel::DIFFICULT);
        $m2 = DifficultyModifier::forLevel(DifficultyLevel::DIFFICULT);

        $base = new StateTransition(trustDelta: 3, irritationDelta: 4);

        $r1 = $m1->apply($base);
        $r2 = $m2->apply($base);

        $this->assertSame($r1->toArray(), $r2->toArray());
    }

    public function test_to_array(): void
    {
        $m = DifficultyModifier::forLevel(DifficultyLevel::EXPERT);

        $array = $m->toArray();

        $this->assertSame(0.5, $array['trust_gain_multiplier']);
        $this->assertSame(1.5, $array['trust_loss_multiplier']);
        $this->assertSame(85, $array['disclosure_resistance']);
        $this->assertArrayHasKey('boundary_persistence', $array);
    }

    public function test_is_default(): void
    {
        $normal = DifficultyModifier::forLevel(DifficultyLevel::NORMAL);
        $this->assertTrue($normal->isDefault());

        $custom = new DifficultyModifier(trustGainMultiplier: 2.0);
        $this->assertFalse($custom->isDefault());
    }

    public function test_default_constructor_is_normal(): void
    {
        $m = new DifficultyModifier;

        $this->assertSame(1.0, $m->trustGainMultiplier);
        $this->assertSame(50, $m->irritationSensitivity);
        $this->assertTrue($m->isDefault());
    }

    public function test_engine_applies_difficulty_to_transition(): void
    {
        $engine = new RoleplayDirectorEngine;
        $state = DirectorState::default();

        $expert = DifficultyModifier::forLevel(DifficultyLevel::EXPERT);
        $engine->setDifficultyModifier($expert);

        $event = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING);
        $result = $engine->applyEvent($event, $state);

        $this->assertTrue($result->accepted);
        $this->assertSame(52, $result->state->getTrust());
    }

    public function test_expert_trust_gain_is_half_of_normal_in_engine(): void
    {
        $engineNormal = new RoleplayDirectorEngine;
        $engineExpert = new RoleplayDirectorEngine;
        $state = DirectorState::default();

        $engineNormal->setDifficultyModifier(DifficultyModifier::forLevel(DifficultyLevel::NORMAL));
        $engineExpert->setDifficultyModifier(DifficultyModifier::forLevel(DifficultyLevel::EXPERT));

        $event = new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE);

        $normalResult = $engineNormal->applyEvent($event, $state);
        $expertResult = $engineExpert->applyEvent($event, $state);

        $this->assertSame(54, $normalResult->state->getTrust());
        $this->assertSame(52, $expertResult->state->getTrust());
    }

    public function test_beginner_trust_gain_is_higher_in_engine(): void
    {
        $engineBeginner = new RoleplayDirectorEngine;
        $engineNormal = new RoleplayDirectorEngine;
        $state = DirectorState::default();

        $engineBeginner->setDifficultyModifier(DifficultyModifier::forLevel(DifficultyLevel::BEGINNER));
        $engineNormal->setDifficultyModifier(DifficultyModifier::forLevel(DifficultyLevel::NORMAL));

        $event = new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE);

        $beginnerResult = $engineBeginner->applyEvent($event, $state);
        $normalResult = $engineNormal->applyEvent($event, $state);

        $this->assertSame(56, $beginnerResult->state->getTrust());
        $this->assertSame(54, $normalResult->state->getTrust());
    }

    public function test_no_difficulty_modifier_uses_normal_behavior(): void
    {
        $engine = new RoleplayDirectorEngine;
        $state = DirectorState::default();

        $event = new RoleplayEvent(RoleplayEventType::TRUST_SIGNAL);
        $result = $engine->applyEvent($event, $state);

        $this->assertSame(55, $result->state->getTrust());
    }

    public function test_set_difficulty_modifier_null_restores_normal(): void
    {
        $engine = new RoleplayDirectorEngine;
        $state = DirectorState::default();

        $engine->setDifficultyModifier(DifficultyModifier::forLevel(DifficultyLevel::EXPERT));
        $engine->setDifficultyModifier(null);

        $event = new RoleplayEvent(RoleplayEventType::TRUST_SIGNAL);
        $result = $engine->applyEvent($event, $state);

        $this->assertSame(55, $result->state->getTrust());
    }

    public function test_weak_explanation_tolerance_affects_confusion(): void
    {
        $lowTolerance = new DifficultyModifier(weakExplanationTolerance: 15);
        $highTolerance = new DifficultyModifier(weakExplanationTolerance: 80);

        $base = new StateTransition(confusionDelta: 5);

        $lowResult = $lowTolerance->apply($base);
        $highResult = $highTolerance->apply($base);

        $this->assertSame(7, $lowResult->confusionDelta);
        $this->assertSame(4, $highResult->confusionDelta);
    }

    public function test_non_positive_confusion_not_scaled(): void
    {
        $lowTolerance = new DifficultyModifier(weakExplanationTolerance: 15);
        $base = new StateTransition(confusionDelta: -3);

        $result = $lowTolerance->apply($base);

        $this->assertSame(-3, $result->confusionDelta);
    }

    public function test_custom_non_numeric_values_use_defaults(): void
    {
        $m = DifficultyModifier::fromCustomConfig([
            'trust_gain_multiplier' => 'not-a-number',
            'irritation_sensitivity' => null,
        ]);

        $this->assertSame(1.0, $m->trustGainMultiplier);
        $this->assertSame(50, $m->irritationSensitivity);
    }
}
