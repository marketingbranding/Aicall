<?php

namespace Tests\Unit;

use App\Services\Director\AiEndEligibilityResult;
use App\Services\Director\BoundaryState;
use App\Services\Director\BoundaryTransition;
use App\Services\Director\ConversationPhase;
use App\Services\Director\ConversationPhaseTransition;
use App\Services\Director\DirectorNote;
use App\Services\Director\DirectorSessionEvent;
use App\Services\Director\DirectorSessionSummary;
use App\Services\Director\DirectorSessionSummaryBuilder;
use App\Services\Director\HiddenInfoState;
use App\Services\Director\HiddenInfoTransition;
use App\Services\Director\ObjectionState;
use App\Services\Director\ObjectionTransition;
use App\Services\Director\RoleplayEventType;
use Tests\TestCase;

class DirectorSessionSummaryBuilderTest extends TestCase
{
    private DirectorSessionSummaryBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new DirectorSessionSummaryBuilder;
    }

    public function test_summary_includes_major_objection_events(): void
    {
        $t1 = new ObjectionTransition(
            objectionKey: 'installment',
            fromState: ObjectionState::DORMANT,
            toState: ObjectionState::ACTIVE_VISIBLE,
            triggeredBy: RoleplayEventType::OBJECTION_TRIGGERED,
            accepted: true,
        );

        $t2 = new ObjectionTransition(
            objectionKey: 'installment',
            fromState: ObjectionState::ACTIVE_VISIBLE,
            toState: ObjectionState::ACKNOWLEDGED,
            triggeredBy: RoleplayEventType::OBJECTION_ACKNOWLEDGED,
            accepted: true,
        );

        $t3 = new ObjectionTransition(
            objectionKey: 'installment',
            fromState: ObjectionState::ACKNOWLEDGED,
            toState: ObjectionState::PARTIALLY_RESOLVED,
            triggeredBy: RoleplayEventType::OBJECTION_PARTIALLY_RESOLVED,
            accepted: true,
        );

        $this->builder->recordObjectionTransition($t1, 3);
        $this->builder->recordObjectionTransition($t2, 7);
        $this->builder->recordObjectionTransition($t3, 12);

        $summary = $this->builder->build();

        $this->assertCount(3, $summary->events);
        $this->assertSame('objection', $summary->events[0]->category);
        $this->assertSame(3, $summary->events[0]->turn);
        $this->assertStringContainsString('installment', $summary->events[0]->description);
        $this->assertStringContainsString('visible', $summary->events[0]->description);
    }

    public function test_summary_includes_hidden_info_disclosure_events(): void
    {
        $t1 = new HiddenInfoTransition(
            key: 'slik_issue',
            fromState: HiddenInfoState::LOCKED,
            toState: HiddenInfoState::ELIGIBLE,
            triggeredBy: RoleplayEventType::RELEVANT_FOLLOW_UP,
            accepted: true,
        );

        $t2 = new HiddenInfoTransition(
            key: 'slik_issue',
            fromState: HiddenInfoState::ELIGIBLE,
            toState: HiddenInfoState::DISCLOSED_PARTIAL,
            triggeredBy: RoleplayEventType::EMPATHIC_RESPONSE,
            accepted: true,
        );

        $this->builder->recordHiddenInfoTransition($t1, 5);
        $this->builder->recordHiddenInfoTransition($t2, 10);

        $summary = $this->builder->build();

        $this->assertCount(2, $summary->events);
        $this->assertSame('hidden_info', $summary->events[0]->category);
        $this->assertSame(5, $summary->events[0]->turn);
        $this->assertStringContainsString('eligible', $summary->events[0]->description);
        $this->assertStringContainsString('partially', $summary->events[1]->description);
    }

    public function test_summary_includes_boundary_events(): void
    {
        $t1 = new BoundaryTransition(
            fromState: BoundaryState::NOT_TESTED,
            toState: BoundaryState::MILD_TEST_OCCURRED,
            triggeredBy: RoleplayEventType::CUSTOMER_BOUNDARY_TEST,
            accepted: true,
        );

        $t2 = new BoundaryTransition(
            fromState: BoundaryState::MILD_TEST_OCCURRED,
            toState: BoundaryState::CLEAR_BOUNDARY_ESTABLISHED,
            triggeredBy: RoleplayEventType::CLEAR_PROFESSIONAL_REDIRECTION,
            accepted: true,
        );

        $this->builder->recordBoundaryTransition($t1, 8);
        $this->builder->recordBoundaryTransition($t2, 12);

        $summary = $this->builder->build();

        $this->assertCount(2, $summary->events);
        $this->assertSame('boundary', $summary->events[0]->category);
        $this->assertSame(8, $summary->events[0]->turn);
        $this->assertStringContainsString('tested', $summary->events[0]->description);
    }

    public function test_summary_includes_phase_changes(): void
    {
        $pt1 = new ConversationPhaseTransition(
            fromPhase: ConversationPhase::OPENING,
            toPhase: ConversationPhase::RAPPORT,
            triggeredBy: RoleplayEventType::GOOD_OPENING,
            accepted: true,
        );

        $pt2 = new ConversationPhaseTransition(
            fromPhase: ConversationPhase::RAPPORT,
            toPhase: ConversationPhase::DISCOVERY,
            triggeredBy: RoleplayEventType::RELEVANT_FOLLOW_UP,
            accepted: true,
        );

        $this->builder->recordPhaseTransition($pt1, 2);
        $this->builder->recordPhaseTransition($pt2, 6);

        $summary = $this->builder->build();

        $this->assertCount(2, $summary->events);
        $this->assertSame('phase_change', $summary->events[0]->category);
        $this->assertSame(2, $summary->events[0]->turn);
        $this->assertSame(6, $summary->events[1]->turn);
    }

    public function test_summary_includes_ai_ending_reason_when_eligible(): void
    {
        $result = new AiEndEligibilityResult(
            eligible: true,
            reasonCode: 'trust_collapse',
            directorNote: 'End naturally.',
        );

        $this->builder->recordAiEndEligibility($result);

        $summary = $this->builder->build();

        $this->assertSame('trust_collapse', $summary->aiEndingReason);
        $this->assertSame('End naturally.', $summary->aiEndingNote);
    }

    public function test_summary_excludes_rejected_objection_transitions(): void
    {
        $rejected = new ObjectionTransition(
            objectionKey: 'price',
            fromState: ObjectionState::DORMANT,
            toState: ObjectionState::DORMANT,
            triggeredBy: RoleplayEventType::UNSUPPORTED_CLAIM,
            accepted: false,
            rejectionReason: 'Not applicable',
        );

        $this->builder->recordObjectionTransition($rejected, 1);

        $summary = $this->builder->build();
        $this->assertCount(0, $summary->events);
    }

    public function test_summary_excludes_self_transitions(): void
    {
        $selfTransition = new ObjectionTransition(
            objectionKey: 'price',
            fromState: ObjectionState::DORMANT,
            toState: ObjectionState::DORMANT,
            triggeredBy: RoleplayEventType::OBJECTION_TRIGGERED,
            accepted: true,
        );

        $this->builder->recordObjectionTransition($selfTransition, 1);

        $summary = $this->builder->build();
        $this->assertCount(0, $summary->events);
    }

    public function test_summary_excludes_rejected_phase_transitions(): void
    {
        $rejected = new ConversationPhaseTransition(
            fromPhase: ConversationPhase::DISCOVERY,
            toPhase: ConversationPhase::DISCOVERY,
            triggeredBy: RoleplayEventType::AGGRESSIVE_CLOSING,
            accepted: false,
            prematureClosing: true,
        );

        $this->builder->recordPhaseTransition($rejected, 5);

        $summary = $this->builder->build();
        $this->assertCount(0, $summary->events);
    }

    public function test_summary_excludes_non_threshold_notes(): void
    {
        $objNote = new DirectorNote('Objection note', 'objection', 2);
        $thresholdNote = new DirectorNote('State threshold', 'state_threshold', 1);

        $this->builder->recordStateThresholds([$objNote, $thresholdNote], 3);

        $summary = $this->builder->build();
        $this->assertCount(1, $summary->events);
        $this->assertSame('state_threshold', $summary->events[0]->category);
    }

    public function test_no_ai_ending_when_not_eligible(): void
    {
        $result = new AiEndEligibilityResult(
            eligible: false,
            reasonCode: 'not_eligible',
        );

        $this->builder->recordAiEndEligibility($result);

        $summary = $this->builder->build();

        $this->assertNull($summary->aiEndingReason);
        $this->assertNull($summary->aiEndingNote);
    }

    public function test_deterministic_output(): void
    {
        $b1 = new DirectorSessionSummaryBuilder;
        $b2 = new DirectorSessionSummaryBuilder;

        $ot = new ObjectionTransition(
            objectionKey: 'location',
            fromState: ObjectionState::DORMANT,
            toState: ObjectionState::ACTIVE_VISIBLE,
            triggeredBy: RoleplayEventType::OBJECTION_TRIGGERED,
            accepted: true,
        );

        $pt = new ConversationPhaseTransition(
            fromPhase: ConversationPhase::DISCOVERY,
            toPhase: ConversationPhase::NEED_EXPLORATION,
            triggeredBy: RoleplayEventType::RELEVANT_FOLLOW_UP,
            accepted: true,
        );

        $b1->recordObjectionTransition($ot, 3);
        $b1->recordPhaseTransition($pt, 5);

        $b2->recordObjectionTransition($ot, 3);
        $b2->recordPhaseTransition($pt, 5);

        $s1 = $b1->build();
        $s2 = $b2->build();

        $this->assertCount(count($s1->events), $s2->events);
        $this->assertSame($s1->events[0]->description, $s2->events[0]->description);
        $this->assertSame($s1->events[1]->turn, $s2->events[1]->turn);
    }

    public function test_output_is_json_serializable(): void
    {
        $ot = new ObjectionTransition(
            objectionKey: 'installment',
            fromState: ObjectionState::DORMANT,
            toState: ObjectionState::ACTIVE_VISIBLE,
            triggeredBy: RoleplayEventType::OBJECTION_TRIGGERED,
            accepted: true,
            directorNote: 'Customer concerned about installments.',
        );

        $this->builder->recordObjectionTransition($ot, 3);

        $summary = $this->builder->build();
        $array = $summary->toArray();

        $this->assertArrayHasKey('events', $array);
        $this->assertArrayHasKey('ai_ending_reason', $array);

        $json = json_encode($array);
        $this->assertNotFalse($json);
        $this->assertJson($json);
        $this->assertStringContainsString('installment', $json);
    }

    public function test_multiple_events_ordered_by_turn(): void
    {
        $ot = new ObjectionTransition(
            objectionKey: 'price',
            fromState: ObjectionState::DORMANT,
            toState: ObjectionState::ACTIVE_VISIBLE,
            triggeredBy: RoleplayEventType::OBJECTION_TRIGGERED,
            accepted: true,
        );

        $pt = new ConversationPhaseTransition(
            fromPhase: ConversationPhase::OPENING,
            toPhase: ConversationPhase::DISCOVERY,
            triggeredBy: RoleplayEventType::GOOD_OPENING,
            accepted: true,
        );

        $bt = new BoundaryTransition(
            fromState: BoundaryState::NOT_TESTED,
            toState: BoundaryState::MILD_TEST_OCCURRED,
            triggeredBy: RoleplayEventType::CUSTOMER_BOUNDARY_TEST,
            accepted: true,
        );

        $this->builder->recordPhaseTransition($pt, 1);
        $this->builder->recordBoundaryTransition($bt, 8);
        $this->builder->recordObjectionTransition($ot, 4);

        $summary = $this->builder->build();

        $this->assertCount(3, $summary->events);
        $this->assertSame(1, $summary->events[0]->turn);
        $this->assertSame(4, $summary->events[1]->turn);
        $this->assertSame(8, $summary->events[2]->turn);
    }

    public function test_premature_closing_included_in_summary(): void
    {
        $pt = new ConversationPhaseTransition(
            fromPhase: ConversationPhase::DISCOVERY,
            toPhase: ConversationPhase::DISCOVERY,
            triggeredBy: RoleplayEventType::AGGRESSIVE_CLOSING,
            accepted: true,
            prematureClosing: true,
        );

        $this->builder->recordPhaseTransition($pt, 6);

        $summary = $this->builder->build();

        $this->assertCount(1, $summary->events);
        $this->assertSame('phase_change', $summary->events[0]->category);
        $this->assertStringContainsString('Premature', $summary->events[0]->description);
    }

    public function test_boundary_transition_with_director_note(): void
    {
        $bt = new BoundaryTransition(
            fromState: BoundaryState::MILD_TEST_OCCURRED,
            toState: BoundaryState::SIGNIFICANT_VIOLATION,
            triggeredBy: RoleplayEventType::SIGNIFICANT_BOUNDARY_VIOLATION,
            accepted: true,
            directorNote: 'A significant boundary violation occurred.',
        );

        $this->builder->recordBoundaryTransition($bt, 15);

        $summary = $this->builder->build();

        $this->assertCount(1, $summary->events);
        $this->assertStringContainsString($bt->directorNote, $summary->events[0]->description);
    }

    public function test_hidden_info_uses_director_note_when_available(): void
    {
        $ht = new HiddenInfoTransition(
            key: 'income',
            fromState: HiddenInfoState::LOCKED,
            toState: HiddenInfoState::ELIGIBLE,
            triggeredBy: RoleplayEventType::TRUST_SIGNAL,
            accepted: true,
            directorNote: 'You may now consider revealing: income details.',
        );

        $this->builder->recordHiddenInfoTransition($ht, 7);

        $summary = $this->builder->build();

        $this->assertCount(1, $summary->events);
        $this->assertStringContainsString($ht->directorNote, $summary->events[0]->description);
    }

    public function test_reset_clears_builder_state(): void
    {
        $ot = new ObjectionTransition(
            objectionKey: 'location',
            fromState: ObjectionState::DORMANT,
            toState: ObjectionState::ACTIVE_VISIBLE,
            triggeredBy: RoleplayEventType::OBJECTION_TRIGGERED,
            accepted: true,
        );

        $this->builder->recordObjectionTransition($ot, 3);
        $this->builder->reset();

        $summary = $this->builder->build();

        $this->assertCount(0, $summary->events);
        $this->assertNull($summary->aiEndingReason);
    }

    public function test_reset_clears_ai_ending(): void
    {
        $result = new AiEndEligibilityResult(
            eligible: true,
            reasonCode: 'trust_collapse',
            directorNote: 'End naturally.',
        );

        $this->builder->recordAiEndEligibility($result);
        $this->builder->reset();

        $summary = $this->builder->build();

        $this->assertNull($summary->aiEndingReason);
        $this->assertNull($summary->aiEndingNote);
    }

    public function test_summary_includes_threshold_state_crossing_notes(): void
    {
        $notes = [
            new DirectorNote('Trust dropped significantly.', 'state_threshold', 1),
            new DirectorNote('Irritation increased.', 'state_threshold', 1),
        ];

        $this->builder->recordStateThresholds($notes, 9);

        $summary = $this->builder->build();

        $this->assertCount(2, $summary->events);
        $this->assertSame('state_threshold', $summary->events[0]->category);
        $this->assertSame(9, $summary->events[0]->turn);
    }

    public function test_empty_builder_produces_empty_summary(): void
    {
        $summary = $this->builder->build();

        $this->assertCount(0, $summary->events);
        $this->assertNull($summary->aiEndingReason);
        $this->assertNull($summary->aiEndingNote);
    }
}
