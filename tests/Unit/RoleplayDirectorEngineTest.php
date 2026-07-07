<?php

namespace Tests\Unit;

use App\Services\Director\DirectorState;
use App\Services\Director\RoleplayDirectorEngine;
use App\Services\Director\RoleplayEvent;
use App\Services\Director\RoleplayEventType;
use Tests\TestCase;

class RoleplayDirectorEngineTest extends TestCase
{
    public function test_initial_state_has_default_values(): void
    {
        $state = DirectorState::default();

        $this->assertSame(50, $state->getTrust());
        $this->assertSame(50, $state->getInterest());
        $this->assertSame(10, $state->getConfusion());
        $this->assertSame(30, $state->getAnxiety());
        $this->assertSame(10, $state->getIrritation());
        $this->assertSame(10, $state->getPressurePerception());
        $this->assertSame(50, $state->getEngagement());
    }

    public function test_initial_state_can_be_custom(): void
    {
        $state = new DirectorState(trust: 20, interest: 80, confusion: 5);

        $this->assertSame(20, $state->getTrust());
        $this->assertSame(80, $state->getInterest());
        $this->assertSame(5, $state->getConfusion());
    }

    public function test_state_values_are_clamped_to_0(): void
    {
        $state = new DirectorState(trust: -10, interest: -5);

        $this->assertSame(0, $state->getTrust());
        $this->assertSame(0, $state->getInterest());
    }

    public function test_state_values_are_clamped_to_100(): void
    {
        $state = new DirectorState(trust: 150, engagement: 120);

        $this->assertSame(100, $state->getTrust());
        $this->assertSame(100, $state->getEngagement());
    }

    public function test_state_apply_without_overflow(): void
    {
        $state = new DirectorState(trust: 95);
        $event = new RoleplayEvent(RoleplayEventType::TRUST_SIGNAL);

        $engine = new RoleplayDirectorEngine;
        $result = $engine->applyEvent($event, $state);

        $this->assertSame(100, $result->state->getTrust());
    }

    public function test_state_apply_without_underflow(): void
    {
        $state = new DirectorState(trust: 3);
        $event = new RoleplayEvent(RoleplayEventType::DISTRUST_SIGNAL);

        $engine = new RoleplayDirectorEngine;
        $result = $engine->applyEvent($event, $state);

        $this->assertSame(0, $result->state->getTrust());
    }

    public function test_active_listening_increases_trust_and_engagement(): void
    {
        $state = DirectorState::default();
        $event = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING);

        $engine = new RoleplayDirectorEngine;
        $result = $engine->applyEvent($event, $state);

        $this->assertSame(53, $result->state->getTrust());
        $this->assertSame(53, $result->state->getEngagement());
        $this->assertTrue($result->accepted);
    }

    public function test_unsupported_claim_decreases_trust_and_increases_irritation(): void
    {
        $state = DirectorState::default();
        $event = new RoleplayEvent(RoleplayEventType::UNSUPPORTED_CLAIM);

        $engine = new RoleplayDirectorEngine;
        $result = $engine->applyEvent($event, $state);

        $this->assertSame(45, $result->state->getTrust());
        $this->assertSame(13, $result->state->getIrritation());
        $this->assertTrue($result->accepted);
    }

    public function test_dismissed_concern_decreases_trust_and_engagement(): void
    {
        $state = DirectorState::default();
        $event = new RoleplayEvent(RoleplayEventType::DISMISSED_CONCERN);

        $engine = new RoleplayDirectorEngine;
        $result = $engine->applyEvent($event, $state);

        $this->assertSame(45, $result->state->getTrust());
        $this->assertSame(45, $result->state->getEngagement());
    }

    public function test_aggressive_closing_increases_pressure_perception(): void
    {
        $state = DirectorState::default();
        $event = new RoleplayEvent(RoleplayEventType::AGGRESSIVE_CLOSING);

        $engine = new RoleplayDirectorEngine;
        $result = $engine->applyEvent($event, $state);

        $this->assertSame(18, $result->state->getPressurePerception());
        $this->assertSame(47, $result->state->getEngagement());
    }

    public function test_deterministic_output_same_event_same_result(): void
    {
        $state = DirectorState::default();
        $event = new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE);

        $engine1 = new RoleplayDirectorEngine;
        $result1 = $engine1->applyEvent($event, $state);

        $engine2 = new RoleplayDirectorEngine;
        $result2 = $engine2->applyEvent($event, $state);

        $this->assertSame(
            $result1->state->toArray(),
            $result2->state->toArray(),
        );
    }

    public function test_repeated_event_is_rejected_by_dedup(): void
    {
        $state = DirectorState::default();
        $event = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING);

        $engine = new RoleplayDirectorEngine;

        $first = $engine->applyEvent($event, $state);
        $this->assertTrue($first->accepted);
$this->assertSame(53, $first->state->getTrust());
 
        $second = $engine->applyEvent($event, $first->state);
        $this->assertFalse($second->accepted);
        $this->assertSame('Duplicate event fingerprint', $second->rejectionReason);
        $this->assertSame(53, $second->state->getTrust());
    }

    public function test_different_events_with_same_fingerprint_are_deduped(): void
    {
        $state = DirectorState::default();
        $eventA = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING, topic: 'greeting');
        $eventB = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING, topic: 'greeting');

        $engine = new RoleplayDirectorEngine;

        $first = $engine->applyEvent($eventA, $state);
        $this->assertTrue($first->accepted);

        $second = $engine->applyEvent($eventB, $state);
        $this->assertFalse($second->accepted);
    }

    public function test_same_event_type_different_topic_not_deduped(): void
    {
        $state = DirectorState::default();
        $eventA = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING, topic: 'financial');
        $eventB = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING, topic: 'location');

        $engine = new RoleplayDirectorEngine;

        $first = $engine->applyEvent($eventA, $state);
        $this->assertTrue($first->accepted);

        $second = $engine->applyEvent($eventB, $state);
        $this->assertTrue($second->accepted);
    }

    public function test_engine_has_no_ai_dependency(): void
    {
        $engine = new RoleplayDirectorEngine;
        $state = DirectorState::default();
        $event = new RoleplayEvent(RoleplayEventType::GOOD_OPENING);

        $result = $engine->applyEvent($event, $state);

        $this->assertTrue($result->accepted);
        $this->assertInstanceOf(DirectorState::class, $result->state);
    }

    public function test_all_event_types_produce_valid_transition(): void
    {
        $state = DirectorState::default();
        $engine = new RoleplayDirectorEngine;

        foreach (RoleplayEventType::cases() as $type) {
            $event = new RoleplayEvent($type, topic: 'test_' . $type->value);
            $result = $engine->applyEvent($event, $state);

            $this->assertTrue($result->accepted, "Event {$type->value} should be accepted");
            $this->assertNotNull($result->state);
        }
    }

    public function test_fingerprint_changes_with_key(): void
    {
        $event1 = new RoleplayEvent(
            RoleplayEventType::OBJECTION_TRIGGERED,
            topic: 'installment',
            relatedObjectionKey: 'installment_heavy',
        );
        $event2 = new RoleplayEvent(
            RoleplayEventType::OBJECTION_TRIGGERED,
            topic: 'installment',
            relatedObjectionKey: 'location_far',
        );

        $this->assertNotSame($event1->fingerprint(), $event2->fingerprint());
    }

    public function test_memory_can_be_reset(): void
    {
        $state = DirectorState::default();
        $event = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING);

        $engine = new RoleplayDirectorEngine;

        $engine->applyEvent($event, $state);
        $engine->resetMemory();

        $afterReset = $engine->applyEvent($event, $state);
        $this->assertTrue($afterReset->accepted);
    }

    public function test_state_to_array(): void
    {
        $state = new DirectorState(trust: 25, interest: 75);

        $array = $state->toArray();

        $this->assertSame(25, $array['trust']);
        $this->assertSame(75, $array['interest']);
        $this->assertArrayHasKey('trust', $array);
        $this->assertArrayHasKey('interest', $array);
        $this->assertArrayHasKey('confusion', $array);
        $this->assertArrayHasKey('anxiety', $array);
        $this->assertArrayHasKey('irritation', $array);
        $this->assertArrayHasKey('pressure_perception', $array);
        $this->assertArrayHasKey('engagement', $array);
    }
}
