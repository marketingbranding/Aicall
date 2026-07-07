<?php

namespace App\Services\Director;

class AiEndEligibility
{
    private const int TRUST_COLLAPSE_MAX = 20;
    private const int LOW_ENGAGEMENT_MAX = 20;
    private const int HIGH_PRESSURE_MIN = 80;
    private const int DISMISSED_CONCERN_THRESHOLD = 2;
    private const int UNSUPPORTED_CLAIM_THRESHOLD = 3;
    private const int AGGRESSIVE_CLOSING_THRESHOLD = 2;

    private const array EARLY_PHASES = [
        ConversationPhase::OPENING->value => true,
        ConversationPhase::RAPPORT->value => true,
        ConversationPhase::DISCOVERY->value => true,
        ConversationPhase::NEED_EXPLORATION->value => true,
        ConversationPhase::EXPLANATION->value => true,
        ConversationPhase::OBJECTION_HANDLING->value => true,
    ];

    public function evaluate(
        bool $allowAiEndCall,
        DirectorState $state,
        string $boundaryStateValue,
        string $conversationPhaseValue,
        int $dismissedConcernCount,
        int $unsupportedClaimCount,
        int $aggressiveClosingCount,
    ): AiEndEligibilityResult {
        if (!$allowAiEndCall) {
            return new AiEndEligibilityResult(
                eligible: false,
                reasonCode: 'not_enabled',
            );
        }

        if ($boundaryStateValue === BoundaryState::PROFESSIONAL_TERMINATION_ELIGIBLE->value) {
            return new AiEndEligibilityResult(
                eligible: true,
                reasonCode: 'boundary_termination',
                directorNote: 'Boundary violations make continued conversation inappropriate. End naturally.',
            );
        }

        if ($state->getTrust() <= self::TRUST_COLLAPSE_MAX) {
            return new AiEndEligibilityResult(
                eligible: true,
                reasonCode: 'trust_collapse',
                directorNote: 'You no longer believe the conversation is useful. End naturally within your next appropriate response.',
            );
        }

        if ($state->getEngagement() <= self::LOW_ENGAGEMENT_MAX) {
            return new AiEndEligibilityResult(
                eligible: true,
                reasonCode: 'low_engagement',
                directorNote: 'You have lost interest in this conversation. End naturally.',
            );
        }

        if ($state->getPressurePerception() >= self::HIGH_PRESSURE_MIN) {
            return new AiEndEligibilityResult(
                eligible: true,
                reasonCode: 'high_pressure',
                directorNote: 'You feel too pressured to continue. End the conversation naturally.',
            );
        }

        if ($dismissedConcernCount >= self::DISMISSED_CONCERN_THRESHOLD) {
            return new AiEndEligibilityResult(
                eligible: true,
                reasonCode: 'repeated_dismissal',
                directorNote: 'Your concerns have been repeatedly dismissed. The conversation is no longer productive.',
            );
        }

        if (
            $aggressiveClosingCount >= self::AGGRESSIVE_CLOSING_THRESHOLD
            && isset(self::EARLY_PHASES[$conversationPhaseValue])
        ) {
            return new AiEndEligibilityResult(
                eligible: true,
                reasonCode: 'aggressive_closing_early',
                directorNote: 'The salesperson is pushing too aggressively. End naturally.',
            );
        }

        if ($unsupportedClaimCount >= self::UNSUPPORTED_CLAIM_THRESHOLD) {
            return new AiEndEligibilityResult(
                eligible: true,
                reasonCode: 'repeated_unsupported_claim',
                directorNote: 'Too many unsupported claims have been made. You no longer trust the conversation.',
            );
        }

        if ($conversationPhaseValue === ConversationPhase::ENDING->value) {
            return new AiEndEligibilityResult(
                eligible: true,
                reasonCode: 'natural_ending',
                directorNote: 'The conversation has reached its natural conclusion. End gracefully.',
            );
        }

        return new AiEndEligibilityResult(
            eligible: false,
            reasonCode: 'not_eligible',
        );
    }
}
