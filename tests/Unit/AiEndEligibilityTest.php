<?php

namespace Tests\Unit;

use App\Services\Director\AiEndEligibility;
use App\Services\Director\AiEndEligibilityResult;
use App\Services\Director\BoundaryState;
use App\Services\Director\ConversationPhase;
use App\Services\Director\DirectorState;
use Tests\TestCase;

class AiEndEligibilityTest extends TestCase
{
    private AiEndEligibility $eligibility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eligibility = new AiEndEligibility;
    }

    public function test_disabled_ai_ending_always_false(): void
    {
        $state = new DirectorState(trust: 0, engagement: 0, pressurePerception: 100);

        $result = $this->eligibility->evaluate(
            allowAiEndCall: false,
            state: $state,
            boundaryStateValue: BoundaryState::PROFESSIONAL_TERMINATION_ELIGIBLE->value,
            conversationPhaseValue: ConversationPhase::ENDING->value,
            dismissedConcernCount: 10,
            unsupportedClaimCount: 10,
            aggressiveClosingCount: 10,
        );

        $this->assertFalse($result->eligible);
        $this->assertSame('not_enabled', $result->reasonCode);
    }

    public function test_trust_collapse_makes_eligible(): void
    {
        $state = new DirectorState(trust: 15);

        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: $state,
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::DISCOVERY->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $this->assertTrue($result->eligible);
        $this->assertSame('trust_collapse', $result->reasonCode);
        $this->assertNotNull($result->directorNote);
    }

    public function test_boundary_termination_has_higher_priority_than_trust(): void
    {
        $state = new DirectorState(trust: 15, engagement: 15);

        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: $state,
            boundaryStateValue: BoundaryState::PROFESSIONAL_TERMINATION_ELIGIBLE->value,
            conversationPhaseValue: ConversationPhase::DISCOVERY->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $this->assertTrue($result->eligible);
        $this->assertSame('boundary_termination', $result->reasonCode);
    }

    public function test_low_engagement_makes_eligible(): void
    {
        $state = new DirectorState(trust: 50, engagement: 15);

        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: $state,
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::DISCOVERY->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $this->assertTrue($result->eligible);
        $this->assertSame('low_engagement', $result->reasonCode);
    }

    public function test_high_pressure_makes_eligible(): void
    {
        $state = new DirectorState(trust: 50, engagement: 50, pressurePerception: 85);

        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: $state,
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::DISCOVERY->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $this->assertTrue($result->eligible);
        $this->assertSame('high_pressure', $result->reasonCode);
    }

    public function test_repeated_dismissal_makes_eligible(): void
    {
        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: DirectorState::default(),
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::DISCOVERY->value,
            dismissedConcernCount: 2,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $this->assertTrue($result->eligible);
        $this->assertSame('repeated_dismissal', $result->reasonCode);
    }

    public function test_aggressive_closing_in_early_phase_makes_eligible(): void
    {
        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: DirectorState::default(),
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::RAPPORT->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 2,
        );

        $this->assertTrue($result->eligible);
        $this->assertSame('aggressive_closing_early', $result->reasonCode);
    }

    public function test_aggressive_closing_in_late_phase_not_eligible(): void
    {
        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: DirectorState::default(),
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::COMMITMENT->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 2,
        );

        $this->assertFalse($result->eligible);
        $this->assertSame('not_eligible', $result->reasonCode);
    }

    public function test_repeated_unsupported_claim_makes_eligible(): void
    {
        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: DirectorState::default(),
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::EXPLANATION->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 3,
            aggressiveClosingCount: 0,
        );

        $this->assertTrue($result->eligible);
        $this->assertSame('repeated_unsupported_claim', $result->reasonCode);
    }

    public function test_natural_ending_phase_makes_eligible(): void
    {
        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: DirectorState::default(),
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::ENDING->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $this->assertTrue($result->eligible);
        $this->assertSame('natural_ending', $result->reasonCode);
    }

    public function test_normal_conversation_not_eligible(): void
    {
        $state = new DirectorState(trust: 50, engagement: 50, pressurePerception: 30);

        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: $state,
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::DISCOVERY->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $this->assertFalse($result->eligible);
        $this->assertSame('not_eligible', $result->reasonCode);
    }

    public function test_trust_boundary_not_collapsed_not_eligible(): void
    {
        $state = new DirectorState(trust: 25, engagement: 50, pressurePerception: 30);

        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: $state,
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::DISCOVERY->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $this->assertFalse($result->eligible);
    }

    public function test_engagement_not_low_enough_not_eligible(): void
    {
        $state = new DirectorState(trust: 50, engagement: 25, pressurePerception: 30);

        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: $state,
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::DISCOVERY->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $this->assertFalse($result->eligible);
    }

    public function test_pressure_not_high_enough_not_eligible(): void
    {
        $state = new DirectorState(trust: 50, engagement: 50, pressurePerception: 75);

        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: $state,
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::DISCOVERY->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $this->assertFalse($result->eligible);
    }

    public function test_dismissal_below_threshold_not_eligible(): void
    {
        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: DirectorState::default(),
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::DISCOVERY->value,
            dismissedConcernCount: 1,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $this->assertFalse($result->eligible);
    }

    public function test_unsupported_claim_below_threshold_not_eligible(): void
    {
        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: DirectorState::default(),
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::EXPLANATION->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 2,
            aggressiveClosingCount: 0,
        );

        $this->assertFalse($result->eligible);
    }

    public function test_aggressive_closing_below_threshold_not_eligible(): void
    {
        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: DirectorState::default(),
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::DISCOVERY->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 1,
        );

        $this->assertFalse($result->eligible);
    }

    public function test_reason_code_is_deterministic(): void
    {
        $state = new DirectorState(trust: 10, engagement: 10, pressurePerception: 90);

        $r1 = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: $state,
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::DISCOVERY->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $r2 = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: $state,
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::DISCOVERY->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $this->assertTrue($r1->eligible);
        $this->assertSame($r1->reasonCode, $r2->reasonCode);
        $this->assertSame($r1->directorNote, $r2->directorNote);
    }

    public function test_director_note_does_not_contain_numeric_state(): void
    {
        $state = new DirectorState(trust: 10, engagement: 10, pressurePerception: 90);

        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: $state,
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::DISCOVERY->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $this->assertNotNull($result->directorNote);
        $this->assertDoesNotMatchRegularExpression('/\d/', $result->directorNote);
    }

    public function test_to_array(): void
    {
        $result = new AiEndEligibilityResult(
            eligible: true,
            reasonCode: 'trust_collapse',
            directorNote: 'End naturally.',
        );

        $array = $result->toArray();

        $this->assertTrue($array['eligible']);
        $this->assertSame('trust_collapse', $array['reason_code']);
        $this->assertSame('End naturally.', $array['director_note']);
    }

    public function test_boundary_termination_with_high_values(): void
    {
        $state = new DirectorState(trust: 50, engagement: 50, pressurePerception: 40);

        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: $state,
            boundaryStateValue: BoundaryState::PROFESSIONAL_TERMINATION_ELIGIBLE->value,
            conversationPhaseValue: ConversationPhase::CLOSING->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $this->assertTrue($result->eligible);
        $this->assertSame('boundary_termination', $result->reasonCode);
    }

    public function test_natural_ending_honored_even_without_other_signals(): void
    {
        $state = DirectorState::default();

        $result = $this->eligibility->evaluate(
            allowAiEndCall: true,
            state: $state,
            boundaryStateValue: BoundaryState::CLEAR_BOUNDARY_ESTABLISHED->value,
            conversationPhaseValue: ConversationPhase::ENDING->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $this->assertTrue($result->eligible);
        $this->assertSame('natural_ending', $result->reasonCode);
    }

    public function test_not_eligible_has_null_note(): void
    {
        $result = $this->eligibility->evaluate(
            allowAiEndCall: false,
            state: DirectorState::default(),
            boundaryStateValue: BoundaryState::NOT_TESTED->value,
            conversationPhaseValue: ConversationPhase::DISCOVERY->value,
            dismissedConcernCount: 0,
            unsupportedClaimCount: 0,
            aggressiveClosingCount: 0,
        );

        $this->assertNull($result->directorNote);
    }
}
