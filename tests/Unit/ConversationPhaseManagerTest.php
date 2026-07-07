<?php

namespace Tests\Unit;

use App\Services\Director\ConversationPhase;
use App\Services\Director\ConversationPhaseManager;
use App\Services\Director\RoleplayEvent;
use App\Services\Director\RoleplayEventType;
use Tests\TestCase;

class ConversationPhaseManagerTest extends TestCase
{
    public function test_starts_from_opening_by_default(): void
    {
        $cpm = new ConversationPhaseManager;

        $this->assertSame(ConversationPhase::OPENING, $cpm->getCurrentPhase());
    }

    public function test_starts_from_configured_phase(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::DISCOVERY);

        $this->assertSame(ConversationPhase::DISCOVERY, $cpm->getCurrentPhase());
    }

    public function test_opening_can_move_to_rapport(): void
    {
        $cpm = new ConversationPhaseManager;
        $event = new RoleplayEvent(RoleplayEventType::GOOD_OPENING);

        $result = $cpm->processEvent($event);

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::RAPPORT, $result->toPhase);
        $this->assertSame(ConversationPhase::RAPPORT, $cpm->getCurrentPhase());
    }

    public function test_opening_can_move_to_discovery(): void
    {
        $cpm = new ConversationPhaseManager;
        $event = new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP);

        $result = $cpm->processEvent($event);

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::DISCOVERY, $result->toPhase);
        $this->assertSame(ConversationPhase::DISCOVERY, $cpm->getCurrentPhase());
    }

    public function test_discovery_can_move_to_need_exploration(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::DISCOVERY);
        $event = new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP);

        $result = $cpm->processEvent($event);

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::NEED_EXPLORATION, $result->toPhase);
        $this->assertSame(ConversationPhase::NEED_EXPLORATION, $cpm->getCurrentPhase());
    }

    public function test_explanation_can_move_to_objection_handling(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::EXPLANATION);
        $event = new RoleplayEvent(RoleplayEventType::OBJECTION_TRIGGERED);

        $result = $cpm->processEvent($event);

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::OBJECTION_HANDLING, $result->toPhase);
        $this->assertSame(ConversationPhase::OBJECTION_HANDLING, $cpm->getCurrentPhase());
    }

    public function test_objection_handling_can_move_back_to_discovery(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::OBJECTION_HANDLING);
        $event = new RoleplayEvent(RoleplayEventType::CONCERN_DISCOVERED);

        $result = $cpm->processEvent($event);

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::DISCOVERY, $result->toPhase);
        $this->assertSame(ConversationPhase::DISCOVERY, $cpm->getCurrentPhase());
    }

    public function test_premature_aggressive_closing_is_detected(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::DISCOVERY);
        $event = new RoleplayEvent(RoleplayEventType::AGGRESSIVE_CLOSING);

        $this->assertTrue($cpm->isPrematureClosingEvent($event));
        $this->assertSame(ConversationPhase::DISCOVERY, $cpm->getCurrentPhase());
    }

    public function test_aggressive_closing_in_early_phase_detected_as_premature(): void
    {
        $cpm = new ConversationPhaseManager;
        $cpm->processEvent(new RoleplayEvent(RoleplayEventType::GOOD_OPENING));
        $this->assertSame(ConversationPhase::RAPPORT, $cpm->getCurrentPhase());

        $event = new RoleplayEvent(RoleplayEventType::AGGRESSIVE_CLOSING);

        $this->assertTrue($cpm->isPrematureClosingEvent($event));
        $this->assertSame(ConversationPhase::RAPPORT, $cpm->getCurrentPhase());
    }

    public function test_aggressive_closing_in_late_phase_is_not_premature(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::COMMITMENT);
        $event = new RoleplayEvent(RoleplayEventType::AGGRESSIVE_CLOSING);

        $this->assertFalse($cpm->isPrematureClosingEvent($event));
        $this->assertSame(ConversationPhase::COMMITMENT, $cpm->getCurrentPhase());
    }

    public function test_appropriate_next_step_can_move_to_commitment(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::EXPLANATION);
        $event = new RoleplayEvent(RoleplayEventType::APPROPRIATE_NEXT_STEP);

        $result = $cpm->processEvent($event);

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::COMMITMENT, $result->toPhase);
        $this->assertSame(ConversationPhase::COMMITMENT, $cpm->getCurrentPhase());
    }

    public function test_appropriate_next_step_can_move_to_closing(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::COMMITMENT);
        $event = new RoleplayEvent(RoleplayEventType::APPROPRIATE_NEXT_STEP);

        $result = $cpm->processEvent($event);

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::CLOSING, $cpm->getCurrentPhase());
    }

    public function test_closing_can_move_to_ending(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::CLOSING);
        $event = new RoleplayEvent(RoleplayEventType::APPROPRIATE_NEXT_STEP);

        $result = $cpm->processEvent($event);

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::ENDING, $result->toPhase);
        $this->assertSame(ConversationPhase::ENDING, $cpm->getCurrentPhase());
    }

    public function test_ending_phase_is_terminal(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::ENDING);
        $event = new RoleplayEvent(RoleplayEventType::GOOD_OPENING);

        $result = $cpm->processEvent($event);

        $this->assertNull($result);
        $this->assertSame(ConversationPhase::ENDING, $cpm->getCurrentPhase());
    }

    public function test_transition_to_ending(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::DISCOVERY);

        $result = $cpm->transitionToEnding();

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::ENDING, $cpm->getCurrentPhase());
        $this->assertSame(ConversationPhase::DISCOVERY, $result->fromPhase);
    }

    public function test_invalid_transitions_safely_ignored(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::RAPPORT);
        $event = new RoleplayEvent(RoleplayEventType::TRUST_SIGNAL);

        $this->assertNull($cpm->processEvent($event));
        $this->assertSame(ConversationPhase::RAPPORT, $cpm->getCurrentPhase());
    }

    public function test_deterministic_output(): void
    {
        $cpm1 = new ConversationPhaseManager;
        $cpm2 = new ConversationPhaseManager;

        $events = [
            new RoleplayEvent(RoleplayEventType::GOOD_OPENING),
            new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP),
            new RoleplayEvent(RoleplayEventType::CONCERN_DISCOVERED),
        ];

        $r1 = null;
        foreach ($events as $e) {
            $r1 = $cpm1->processEvent($e);
        }

        $r2 = null;
        foreach ($events as $e) {
            $r2 = $cpm2->processEvent($e);
        }

        $this->assertSame($r1->toArray(), $r2->toArray());
        $this->assertSame($cpm1->getCurrentPhase(), $cpm2->getCurrentPhase());
    }

    public function test_backward_transition_from_explanation_to_discovery(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::EXPLANATION);
        $event = new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP);

        $result = $cpm->processEvent($event);

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::DISCOVERY, $result->toPhase);
        $this->assertSame(ConversationPhase::EXPLANATION, $result->fromPhase);
    }

    public function test_rapport_can_directly_jump_to_objection_handling(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::RAPPORT);
        $event = new RoleplayEvent(RoleplayEventType::OBJECTION_TRIGGERED);

        $result = $cpm->processEvent($event);

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::OBJECTION_HANDLING, $cpm->getCurrentPhase());
    }

    public function test_can_reset(): void
    {
        $cpm = new ConversationPhaseManager;
        $cpm->processEvent(new RoleplayEvent(RoleplayEventType::GOOD_OPENING));
        $this->assertSame(ConversationPhase::RAPPORT, $cpm->getCurrentPhase());

        $cpm->reset();

        $this->assertSame(ConversationPhase::OPENING, $cpm->getCurrentPhase());

        $result = $cpm->processEvent(new RoleplayEvent(RoleplayEventType::GOOD_OPENING));
        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
    }

    public function test_can_reset_to_custom_phase(): void
    {
        $cpm = new ConversationPhaseManager;

        $cpm->reset(ConversationPhase::EXPLANATION);

        $this->assertSame(ConversationPhase::EXPLANATION, $cpm->getCurrentPhase());
    }

    public function test_to_array(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::EXPLANATION);
        $cpm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_TRIGGERED));

        $array = $cpm->toArray();

        $this->assertSame('OBJECTION_HANDLING', $array['current_phase']);
        $this->assertSame(1, $array['transition_count']);
    }

    public function test_is_in_early_phase(): void
    {
        $cpm = new ConversationPhaseManager;

        $this->assertTrue($cpm->isInEarlyPhase());

        $cpm = new ConversationPhaseManager(ConversationPhase::RAPPORT);
        $this->assertTrue($cpm->isInEarlyPhase());

        $cpm = new ConversationPhaseManager(ConversationPhase::DISCOVERY);
        $this->assertTrue($cpm->isInEarlyPhase());

        $cpm = new ConversationPhaseManager(ConversationPhase::NEED_EXPLORATION);
        $this->assertTrue($cpm->isInEarlyPhase());

        $cpm = new ConversationPhaseManager(ConversationPhase::EXPLANATION);
        $this->assertFalse($cpm->isInEarlyPhase());
    }

    public function test_phase_transition_in_engine_result(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::EXPLANATION);
        $event = new RoleplayEvent(RoleplayEventType::OBJECTION_TRIGGERED);

        $result = $cpm->processEvent($event);

        $array = $result->toArray();
        $this->assertSame('EXPLANATION', $array['from_phase']);
        $this->assertSame('OBJECTION_HANDLING', $array['to_phase']);
        $this->assertSame('OBJECTION_TRIGGERED', $array['triggered_by']);
        $this->assertTrue($array['accepted']);
        $this->assertFalse($array['premature_closing']);
    }

    public function test_objection_handling_backward_to_discovery_exists(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::OBJECTION_HANDLING);

        $result = $cpm->processEvent(new RoleplayEvent(RoleplayEventType::CONCERN_DISCOVERED));

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::DISCOVERY, $cpm->getCurrentPhase());
    }

    public function test_objection_handling_backward_via_follow_up(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::OBJECTION_HANDLING);

        $result = $cpm->processEvent(new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP));

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::DISCOVERY, $cpm->getCurrentPhase());
    }

    public function test_closing_backward_to_discovery(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::CLOSING);

        $result = $cpm->processEvent(new RoleplayEvent(RoleplayEventType::CONCERN_DISCOVERED));

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::DISCOVERY, $cpm->getCurrentPhase());
    }

    public function test_weak_opening_moves_to_rapport(): void
    {
        $cpm = new ConversationPhaseManager;
        $event = new RoleplayEvent(RoleplayEventType::WEAK_OPENING);

        $result = $cpm->processEvent($event);

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::RAPPORT, $cpm->getCurrentPhase());
    }

    public function test_active_listening_moves_to_rapport(): void
    {
        $cpm = new ConversationPhaseManager;
        $event = new RoleplayEvent(RoleplayEventType::ACTIVE_LISTENING);

        $result = $cpm->processEvent($event);

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::RAPPORT, $cpm->getCurrentPhase());
    }

    public function test_objection_resolved_candidate_moves_to_commitment(): void
    {
        $cpm = new ConversationPhaseManager(ConversationPhase::OBJECTION_HANDLING);
        $event = new RoleplayEvent(RoleplayEventType::OBJECTION_RESOLVED_CANDIDATE);

        $result = $cpm->processEvent($event);

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame(ConversationPhase::COMMITMENT, $cpm->getCurrentPhase());
    }
}
