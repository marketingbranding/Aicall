<?php

namespace Tests\Unit;

use App\Services\Director\DiminishingReturnCalculator;
use App\Services\Director\DirectorState;
use App\Services\Director\RoleplayDirectorEngine;
use App\Services\Director\RoleplayEvent;
use App\Services\Director\RoleplayEventType;
use App\Services\Director\StateTransition;
use Tests\TestCase;

class DiminishingReturnCalculatorTest extends TestCase
{
    public function test_first_positive_event_applies_full_effect(): void
    {
        $state = DirectorState::default();
        $event = new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE);

        $engine = new RoleplayDirectorEngine;
        $result = $engine->applyEvent($event, $state);

        $this->assertTrue($result->accepted);
        $this->assertSame(54, $result->state->getTrust());
        $this->assertSame(4, $result->appliedTransition->trustDelta);
    }

    public function test_repeated_similar_positive_event_applies_reduced_effect(): void
    {
        $state = DirectorState::default();
        $engine = new RoleplayDirectorEngine;

        $event1 = new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: 'first');
        $first = $engine->applyEvent($event1, $state);
        $this->assertSame(54, $first->state->getTrust());
        $this->assertSame(4, $first->appliedTransition->trustDelta);

        $event2 = new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: 'second');
        $second = $engine->applyEvent($event2, $first->state);
        $this->assertTrue($second->accepted);
        $this->assertSame(2, $second->appliedTransition->trustDelta);
        $this->assertSame(56, $second->state->getTrust());
    }

    public function test_third_repeated_positive_event_has_minimal_effect(): void
    {
        $state = DirectorState::default();
        $engine = new RoleplayDirectorEngine;

        $e1 = new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: 'a');
        $r1 = $engine->applyEvent($e1, $state);
        $this->assertSame(4, $r1->appliedTransition->trustDelta);

        $e2 = new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: 'b');
        $r2 = $engine->applyEvent($e2, $r1->state);
        $this->assertSame(2, $r2->appliedTransition->trustDelta);

        $e3 = new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: 'c');
        $r3 = $engine->applyEvent($e3, $r2->state);
        $this->assertSame(1, $r3->appliedTransition->trustDelta);
        $this->assertSame(57, $r3->state->getTrust());
    }

    public function test_fourth_repeated_positive_event_has_no_positive_effect(): void
    {
        $state = DirectorState::default();
        $engine = new RoleplayDirectorEngine;

        $topics = ['a', 'b', 'c', 'd'];
        $current = $state;

        for ($i = 0; $i < 3; $i++) {
            $e = new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: $topics[$i]);
            $r = $engine->applyEvent($e, $current);
            $current = $r->state;
        }

        $e4 = new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: $topics[3]);
        $r4 = $engine->applyEvent($e4, $current);
        $this->assertSame(0, $r4->appliedTransition->trustDelta);
        $this->assertSame(0, $r4->appliedTransition->engagementDelta);
    }

    public function test_active_listening_diminishes_on_repeat(): void
    {
        $state = DirectorState::default();
        $engine = new RoleplayDirectorEngine;

        $e1 = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING, topic: 'first');
        $r1 = $engine->applyEvent($e1, $state);
        $this->assertSame(3, $r1->appliedTransition->trustDelta);
        $this->assertSame(3, $r1->appliedTransition->engagementDelta);

        $e2 = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING, topic: 'second');
        $r2 = $engine->applyEvent($e2, $r1->state);
        $this->assertSame(2, $r2->appliedTransition->trustDelta);
        $this->assertSame(2, $r2->appliedTransition->engagementDelta);
    }

    public function test_negative_events_are_not_softened_on_repeat(): void
    {
        $state = DirectorState::default();
        $engine = new RoleplayDirectorEngine;

        $e1 = new RoleplayEvent(RoleplayEventType::UNSUPPORTED_CLAIM, topic: 'first');
        $r1 = $engine->applyEvent($e1, $state);
        $this->assertSame(-5, $r1->appliedTransition->trustDelta);
        $this->assertSame(3, $r1->appliedTransition->irritationDelta);

        $e2 = new RoleplayEvent(RoleplayEventType::UNSUPPORTED_CLAIM, topic: 'second');
        $r2 = $engine->applyEvent($e2, $r1->state);
        $this->assertSame(-5, $r2->appliedTransition->trustDelta);
        $this->assertSame(3, $r2->appliedTransition->irritationDelta);
    }

    public function test_dismissed_concern_not_softened_on_repeat(): void
    {
        $state = DirectorState::default();
        $engine = new RoleplayDirectorEngine;

        $e1 = new RoleplayEvent(RoleplayEventType::DISMISSED_CONCERN, topic: 'first');
        $r1 = $engine->applyEvent($e1, $state);
        $this->assertSame(-5, $r1->appliedTransition->trustDelta);

        $e2 = new RoleplayEvent(RoleplayEventType::DISMISSED_CONCERN, topic: 'second');
        $r2 = $engine->applyEvent($e2, $r1->state);
        $this->assertSame(-5, $r2->appliedTransition->trustDelta);
    }

    public function test_different_event_types_do_not_interfere(): void
    {
        $state = DirectorState::default();
        $engine = new RoleplayDirectorEngine;

        $e1 = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING, topic: 'a');
        $r1 = $engine->applyEvent($e1, $state);
        $this->assertSame(3, $r1->appliedTransition->trustDelta);

        $e2 = new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: 'b');
        $r2 = $engine->applyEvent($e2, $r1->state);
        $this->assertSame(4, $r2->appliedTransition->trustDelta);
    }

    public function test_diminishing_calculator_reset_restores_full_effect(): void
    {
        $state = DirectorState::default();
        $engine = new RoleplayDirectorEngine;

        $e1 = new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: 'a');
        $r1 = $engine->applyEvent($e1, $state);
        $this->assertSame(4, $r1->appliedTransition->trustDelta);

        $e2 = new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: 'b');
        $r2 = $engine->applyEvent($e2, $r1->state);
        $this->assertSame(2, $r2->appliedTransition->trustDelta);

        $engine->resetMemory();

        $e3 = new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: 'c');
        $r3 = $engine->applyEvent($e3, $r2->state);
        $this->assertSame(4, $r3->appliedTransition->trustDelta);
    }

    public function test_fresh_engine_gives_full_effect(): void
    {
        $state = DirectorState::default();

        $engine1 = new RoleplayDirectorEngine;
        $r1 = $engine1->applyEvent(
            new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: 'a'),
            $state,
        );

        $engine2 = new RoleplayDirectorEngine;
        $r2 = $engine2->applyEvent(
            new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: 'b'),
            $state,
        );

        $this->assertSame($r1->appliedTransition->trustDelta, $r2->appliedTransition->trustDelta);
    }

    public function test_positive_event_classification(): void
    {
        $positive = RoleplayEventType::EMPATHIC_RESPONSE;
        $negative = RoleplayEventType::UNSUPPORTED_CLAIM;

        $this->assertTrue(DiminishingReturnCalculator::isPositiveEvent($positive));
        $this->assertFalse(DiminishingReturnCalculator::isPositiveEvent($negative));
    }

    public function test_good_opening_diminishes_on_repeat(): void
    {
        $state = DirectorState::default();
        $engine = new RoleplayDirectorEngine;

        $e1 = new RoleplayEvent(RoleplayEventType::GOOD_OPENING, topic: 'first');
        $r1 = $engine->applyEvent($e1, $state);
        $this->assertSame(3, $r1->appliedTransition->trustDelta);

        $e2 = new RoleplayEvent(RoleplayEventType::GOOD_OPENING, topic: 'second');
        $r2 = $engine->applyEvent($e2, $r1->state);
        $this->assertSame(2, $r2->appliedTransition->trustDelta);
    }

    public function test_trust_signal_diminishes_on_repeat(): void
    {
        $state = new DirectorState(trust: 10);
        $engine = new RoleplayDirectorEngine;

        $e1 = new RoleplayEvent(RoleplayEventType::TRUST_SIGNAL, topic: 'a');
        $r1 = $engine->applyEvent($e1, $state);
        $this->assertSame(5, $r1->appliedTransition->trustDelta);

        $e2 = new RoleplayEvent(RoleplayEventType::TRUST_SIGNAL, topic: 'b');
        $r2 = $engine->applyEvent($e2, $r1->state);
        $this->assertSame(3, $r2->appliedTransition->trustDelta);
    }

    public function test_calculator_does_not_grow_beyond_max(): void
    {
        $calculator = new DiminishingReturnCalculator;

        for ($i = 0; $i < 30; $i++) {
            $calculator->record(RoleplayEventType::ACTIVE_LISTENING);
        }

        $this->assertSame(0.0, $calculator->getMultiplier(RoleplayEventType::ACTIVE_LISTENING));
    }

    public function test_calculator_eviction_reduces_oldest_type_count(): void
    {
        $calculator = new DiminishingReturnCalculator;

        $calculator->record(RoleplayEventType::GOOD_OPENING);

        for ($i = 0; $i < 20; $i++) {
            $calculator->record(RoleplayEventType::ACTIVE_LISTENING);
        }

        $calculator->record(RoleplayEventType::EMPATHIC_RESPONSE);

        $this->assertSame(0.0, $calculator->getMultiplier(RoleplayEventType::ACTIVE_LISTENING));
        $this->assertSame(0.5, $calculator->getMultiplier(RoleplayEventType::EMPATHIC_RESPONSE));
        $this->assertSame(1.0, $calculator->getMultiplier(RoleplayEventType::GOOD_OPENING));
    }

    public function test_clear_explanation_diminishes_positive_deltas_only(): void
    {
        $state = new DirectorState(confusion: 50);
        $engine = new RoleplayDirectorEngine;

        $e1 = new RoleplayEvent(RoleplayEventType::CLEAR_EXPLANATION, topic: 'first');
        $r1 = $engine->applyEvent($e1, $state);
        $this->assertSame(-5, $r1->appliedTransition->confusionDelta);
        $this->assertSame(2, $r1->appliedTransition->trustDelta);

        $e2 = new RoleplayEvent(RoleplayEventType::CLEAR_EXPLANATION, topic: 'second');
        $r2 = $engine->applyEvent($e2, $r1->state);
        $this->assertSame(-3, $r2->appliedTransition->confusionDelta);
        $this->assertSame(1, $r2->appliedTransition->trustDelta);
    }
}
