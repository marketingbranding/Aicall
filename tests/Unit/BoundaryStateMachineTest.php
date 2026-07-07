<?php

namespace Tests\Unit;

use App\Services\Director\BoundaryState;
use App\Services\Director\BoundaryStateMachine;
use App\Services\Director\RoleplayEvent;
use App\Services\Director\RoleplayEventType;
use Tests\TestCase;

class BoundaryStateMachineTest extends TestCase
{
    public function test_starts_not_tested(): void
    {
        $bsm = new BoundaryStateMachine;

        $this->assertSame(BoundaryState::NOT_TESTED, $bsm->getCurrentState());
    }

    public function test_mild_test_transition(): void
    {
        $bsm = new BoundaryStateMachine;
        $event = new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST);

        $result = $bsm->processEvent($event);

        $this->assertTrue($result->accepted);
        $this->assertSame(BoundaryState::MILD_TEST_OCCURRED, $result->toState);
        $this->assertSame(BoundaryState::NOT_TESTED, $result->fromState);
        $this->assertSame(BoundaryState::MILD_TEST_OCCURRED, $bsm->getCurrentState());
    }

    public function test_salesperson_participation_transition(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));

        $event = new RoleplayEvent(RoleplayEventType::SALESPERSON_PARTICIPATED_PERSONALLY);
        $result = $bsm->processEvent($event);

        $this->assertTrue($result->accepted);
        $this->assertSame(BoundaryState::SALESPERSON_PARTICIPATED, $result->toState);
        $this->assertSame(BoundaryState::SALESPERSON_PARTICIPATED, $bsm->getCurrentState());
    }

    public function test_indirect_redirection_transition(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));

        $event = new RoleplayEvent(RoleplayEventType::INDIRECT_REDIRECTION);
        $result = $bsm->processEvent($event);

        $this->assertTrue($result->accepted);
        $this->assertSame(BoundaryState::INDIRECTLY_REDIRECTED, $result->toState);
        $this->assertSame(BoundaryState::INDIRECTLY_REDIRECTED, $bsm->getCurrentState());
    }

    public function test_clear_boundary_transition_via_professional_redirection(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));

        $event = new RoleplayEvent(RoleplayEventType::CLEAR_PROFESSIONAL_REDIRECTION);
        $result = $bsm->processEvent($event);

        $this->assertTrue($result->accepted);
        $this->assertSame(BoundaryState::CLEAR_BOUNDARY_ESTABLISHED, $result->toState);
        $this->assertSame(BoundaryState::CLEAR_BOUNDARY_ESTABLISHED, $bsm->getCurrentState());
    }

    public function test_clear_boundary_transition_via_explicit_set(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));

        $event = new RoleplayEvent(RoleplayEventType::EXPLICIT_BOUNDARY_SET);
        $result = $bsm->processEvent($event);

        $this->assertTrue($result->accepted);
        $this->assertSame(BoundaryState::CLEAR_BOUNDARY_ESTABLISHED, $result->toState);
        $this->assertSame(BoundaryState::CLEAR_BOUNDARY_ESTABLISHED, $bsm->getCurrentState());
    }

    public function test_customer_respected_boundary_transition(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CLEAR_PROFESSIONAL_REDIRECTION));

        $event = new RoleplayEvent(RoleplayEventType::CUSTOMER_RESPECTED_BOUNDARY);
        $result = $bsm->processEvent($event);

        $this->assertTrue($result->accepted);
        $this->assertSame(BoundaryState::CUSTOMER_RESPECTED_BOUNDARY, $result->toState);
        $this->assertSame(BoundaryState::CUSTOMER_RESPECTED_BOUNDARY, $bsm->getCurrentState());
    }

    public function test_repeated_boundary_test_transition(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CLEAR_PROFESSIONAL_REDIRECTION));

        $event = new RoleplayEvent(RoleplayEventType::CUSTOMER_REPEATED_BOUNDARY_TEST);
        $result = $bsm->processEvent($event);

        $this->assertTrue($result->accepted);
        $this->assertSame(BoundaryState::CUSTOMER_RETESTED_BOUNDARY, $result->toState);
        $this->assertSame(BoundaryState::CUSTOMER_RETESTED_BOUNDARY, $bsm->getCurrentState());
    }

    public function test_significant_violation_transition(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));

        $event = new RoleplayEvent(RoleplayEventType::SIGNIFICANT_BOUNDARY_VIOLATION);
        $result = $bsm->processEvent($event);

        $this->assertTrue($result->accepted);
        $this->assertSame(BoundaryState::SIGNIFICANT_VIOLATION, $result->toState);
        $this->assertSame(BoundaryState::SIGNIFICANT_VIOLATION, $bsm->getCurrentState());
    }

    public function test_professional_termination_eligibility(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::SIGNIFICANT_BOUNDARY_VIOLATION));

        $event = new RoleplayEvent(RoleplayEventType::SIGNIFICANT_BOUNDARY_VIOLATION);
        $result = $bsm->processEvent($event);

        $this->assertTrue($result->accepted);
        $this->assertSame(BoundaryState::PROFESSIONAL_TERMINATION_ELIGIBLE, $result->toState);
        $this->assertSame(BoundaryState::PROFESSIONAL_TERMINATION_ELIGIBLE, $bsm->getCurrentState());
    }

    public function test_immediate_repeat_boundary_test_is_rejected(): void
    {
        $bsm = new BoundaryStateMachine;
        $event = new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST);

        $first = $bsm->processEvent($event);
        $this->assertTrue($first->accepted);

        $second = $bsm->processEvent($event);
        $this->assertFalse($second->accepted);
        $this->assertSame('Boundary test in cooldown', $second->rejectionReason);
        $this->assertSame(BoundaryState::MILD_TEST_OCCURRED, $bsm->getCurrentState());
    }

    public function test_repeat_boundary_test_allowed_after_cooldown(): void
    {
        $bsm = new BoundaryStateMachine;
        $event = new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST);
        $bsm->processEvent($event);

        $otherEvent = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING);
        $bsm->processEvent($otherEvent);
        $bsm->processEvent($otherEvent);
        $bsm->processEvent($otherEvent);

        $retry = $bsm->processEvent($event);
        $this->assertTrue($retry->accepted);
    }

    public function test_repeated_boundary_test_also_has_cooldown(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CLEAR_PROFESSIONAL_REDIRECTION));

        $event = new RoleplayEvent(RoleplayEventType::CUSTOMER_REPEATED_BOUNDARY_TEST);
        $first = $bsm->processEvent($event);
        $this->assertTrue($first->accepted);
        $this->assertSame(BoundaryState::CUSTOMER_RETESTED_BOUNDARY, $bsm->getCurrentState());

        $second = $bsm->processEvent($event);
        $this->assertFalse($second->accepted);
        $this->assertSame('Boundary test in cooldown', $second->rejectionReason);
    }

    public function test_different_boundary_test_types_not_blocked_by_cooldown(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));

        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CLEAR_PROFESSIONAL_REDIRECTION));

        $retest = new RoleplayEvent(RoleplayEventType::CUSTOMER_REPEATED_BOUNDARY_TEST);
        $result = $bsm->processEvent($retest);
        $this->assertTrue($result->accepted);
    }

    public function test_invalid_event_type_does_not_trigger_transition(): void
    {
        $bsm = new BoundaryStateMachine;
        $event = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING);

        $result = $bsm->processEvent($event);

        $this->assertNotNull($result);
        $this->assertFalse($result->accepted);
        $this->assertStringContainsString('does not trigger transition', $result->rejectionReason);
        $this->assertSame(BoundaryState::NOT_TESTED, $bsm->getCurrentState());
    }

    public function test_deterministic_output(): void
    {
        $bsm1 = new BoundaryStateMachine;
        $bsm2 = new BoundaryStateMachine;

        $event1 = new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST);
        $event2 = new RoleplayEvent(RoleplayEventType::SALESPERSON_PARTICIPATED_PERSONALLY);

        $bsm1->processEvent($event1);
        $r1 = $bsm1->processEvent($event2);

        $bsm2->processEvent($event1);
        $r2 = $bsm2->processEvent($event2);

        $this->assertSame(
            $r1->toArray(),
            $r2->toArray(),
        );
    }

    public function test_can_reset(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));
        $this->assertSame(BoundaryState::MILD_TEST_OCCURRED, $bsm->getCurrentState());

        $bsm->reset();

        $this->assertSame(BoundaryState::NOT_TESTED, $bsm->getCurrentState());

        $result = $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));
        $this->assertTrue($result->accepted);
    }

    public function test_persona_parameters_can_be_configured(): void
    {
        $bsm = new BoundaryStateMachine(
            respectForBoundaries: 80,
            persistenceAfterRedirection: 90,
        );

        $this->assertSame(80, $bsm->getRespectForBoundaries());
        $this->assertSame(90, $bsm->getPersistenceAfterRedirection());
    }

    public function test_persona_parameters_can_be_updated(): void
    {
        $bsm = new BoundaryStateMachine;

        $bsm->setRespectForBoundaries(30);
        $bsm->setPersistenceAfterRedirection(70);

        $this->assertSame(30, $bsm->getRespectForBoundaries());
        $this->assertSame(70, $bsm->getPersistenceAfterRedirection());
    }

    public function test_persona_parameters_clamped(): void
    {
        $bsm = new BoundaryStateMachine;

        $bsm->setRespectForBoundaries(-10);
        $this->assertSame(0, $bsm->getRespectForBoundaries());

        $bsm->setRespectForBoundaries(150);
        $this->assertSame(100, $bsm->getRespectForBoundaries());
    }

    public function test_cooldown_info_available(): void
    {
        $bsm = new BoundaryStateMachine;

        $this->assertSame(0, $bsm->getCooldownRemaining());

        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));

        $this->assertSame(3, $bsm->getCooldownRemaining());

        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING));
        $this->assertSame(2, $bsm->getCooldownRemaining());
    }

    public function test_to_array(): void
    {
        $bsm = new BoundaryStateMachine(
            respectForBoundaries: 60,
            persistenceAfterRedirection: 40,
        );
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));

        $array = $bsm->toArray();

        $this->assertSame('MILD_TEST_OCCURRED', $array['current_state']);
        $this->assertSame(60, $array['respect_for_boundaries']);
        $this->assertSame(40, $array['persistence_after_redirection']);
        $this->assertSame(1, $array['transition_count']);
        $this->assertArrayHasKey('cooldown_remaining', $array);
    }

    public function test_significant_violation_directly_from_not_tested(): void
    {
        $bsm = new BoundaryStateMachine;
        $event = new RoleplayEvent(RoleplayEventType::SIGNIFICANT_BOUNDARY_VIOLATION);

        $result = $bsm->processEvent($event);

        $this->assertTrue($result->accepted);
        $this->assertSame(BoundaryState::SIGNIFICANT_VIOLATION, $bsm->getCurrentState());
    }

    public function test_clear_boundary_from_significant_violation(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::SIGNIFICANT_BOUNDARY_VIOLATION));

        $event = new RoleplayEvent(RoleplayEventType::CLEAR_PROFESSIONAL_REDIRECTION);
        $result = $bsm->processEvent($event);

        $this->assertTrue($result->accepted);
        $this->assertSame(BoundaryState::CLEAR_BOUNDARY_ESTABLISHED, $bsm->getCurrentState());
    }

    public function test_multiple_retests_allowed_after_cooldown(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CLEAR_PROFESSIONAL_REDIRECTION));

        $retest = new RoleplayEvent(RoleplayEventType::CUSTOMER_REPEATED_BOUNDARY_TEST);
        $bsm->processEvent($retest);

        $other = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING);
        $bsm->processEvent($other);
        $bsm->processEvent($other);
        $bsm->processEvent($other);

        $secondRetest = $bsm->processEvent($retest);
        $this->assertTrue($secondRetest->accepted);
        $this->assertSame(BoundaryState::CUSTOMER_RETESTED_BOUNDARY, $bsm->getCurrentState());
    }

    public function test_transition_rejection_with_unknown_event(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));

        $event = new RoleplayEvent(RoleplayEventType::TRUST_SIGNAL);
        $result = $bsm->processEvent($event);

        $this->assertNotNull($result);
        $this->assertFalse($result->accepted);
        $this->assertStringContainsString('does not trigger transition', $result->rejectionReason);
    }

    public function test_director_note_for_mild_test(): void
    {
        $bsm = new BoundaryStateMachine;
        $result = $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));

        $this->assertNotNull($result->directorNote);
        $this->assertStringContainsString('boundary', $result->directorNote);
    }

    public function test_director_note_for_retest_uses_persistence(): void
    {
        $bsm = new BoundaryStateMachine(persistenceAfterRedirection: 80);
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CLEAR_PROFESSIONAL_REDIRECTION));

        $result = $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_REPEATED_BOUNDARY_TEST));

        $this->assertNotNull($result->directorNote);
        $this->assertStringContainsString('persistently', $result->directorNote);
    }

    public function test_boundary_transition_in_engine_result(): void
    {
        $bsm = new BoundaryStateMachine;
        $event = new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST);

        $result = $bsm->processEvent($event);

        $array = $result->toArray();
        $this->assertSame('NOT_TESTED', $array['from_state']);
        $this->assertSame('MILD_TEST_OCCURRED', $array['to_state']);
        $this->assertSame('CUSTOMER_BOUNDARY_TEST', $array['triggered_by']);
        $this->assertTrue($array['accepted']);
        $this->assertNull($array['rejection_reason']);
        $this->assertNotNull($array['director_note']);
    }

    public function test_high_boundary_persistence_shortens_cooldown(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->setBoundaryPersistence(85);
        $event = new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST);

        $bsm->processEvent($event);
        $this->assertSame(2, $bsm->getCooldownRemaining());

        $other = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING);
        $bsm->processEvent($other);
        $this->assertSame(1, $bsm->getCooldownRemaining());

        $bsm->processEvent($other);
        $allow = $bsm->processEvent($event);
        $this->assertTrue($allow->accepted);
    }

    public function test_low_boundary_persistence_lengthens_cooldown(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->setBoundaryPersistence(20);
        $event = new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST);

        $bsm->processEvent($event);
        $this->assertSame(4, $bsm->getCooldownRemaining());

        $other = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING);
        $bsm->processEvent($other);
        $this->assertSame(3, $bsm->getCooldownRemaining());

        $bsm->processEvent($other);
        $stillBlocked = $bsm->processEvent($event);
        $this->assertFalse($stillBlocked->accepted);

        $bsm->processEvent($other);
        $bsm->processEvent($other);
        $allow = $bsm->processEvent($event);
        $this->assertTrue($allow->accepted);
    }

    public function test_normal_boundary_persistence_uses_default_cooldown(): void
    {
        $bsm = new BoundaryStateMachine;
        $event = new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST);

        $bsm->processEvent($event);
        $this->assertSame(3, $bsm->getCooldownRemaining());

        $other = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING);
        $bsm->processEvent($other);
        $bsm->processEvent($other);
        $bsm->processEvent($other);

        $allow = $bsm->processEvent($event);
        $this->assertTrue($allow->accepted);
    }

    public function test_professional_termination_is_terminal(): void
    {
        $bsm = new BoundaryStateMachine;
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST));
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::SIGNIFICANT_BOUNDARY_VIOLATION));
        $bsm->processEvent(new RoleplayEvent(RoleplayEventType::SIGNIFICANT_BOUNDARY_VIOLATION));

        $this->assertSame(BoundaryState::PROFESSIONAL_TERMINATION_ELIGIBLE, $bsm->getCurrentState());

        $event = new RoleplayEvent(RoleplayEventType::CUSTOMER_BOUNDARY_TEST);
        $result = $bsm->processEvent($event);

        $this->assertNotNull($result);
        $this->assertFalse($result->accepted);
        $this->assertStringContainsString('does not trigger transition', $result->rejectionReason);
    }
}
