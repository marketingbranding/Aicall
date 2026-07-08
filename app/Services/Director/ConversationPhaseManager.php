<?php

namespace App\Services\Director;

class ConversationPhaseManager
{
    private const array PHASE_ORDER = [
        ConversationPhase::OPENING->value => 0,
        ConversationPhase::RAPPORT->value => 1,
        ConversationPhase::DISCOVERY->value => 2,
        ConversationPhase::NEED_EXPLORATION->value => 3,
        ConversationPhase::EXPLANATION->value => 4,
        ConversationPhase::OBJECTION_HANDLING->value => 5,
        ConversationPhase::COMMITMENT->value => 6,
        ConversationPhase::CLOSING->value => 7,
        ConversationPhase::ENDING->value => 8,
    ];

    private const array PREMATURE_CLOSING_PHASES = [
        ConversationPhase::OPENING->value => true,
        ConversationPhase::RAPPORT->value => true,
        ConversationPhase::DISCOVERY->value => true,
        ConversationPhase::NEED_EXPLORATION->value => true,
    ];

    private const array TRANSITION_RULES = [
        ConversationPhase::OPENING->value => [
            'GOOD_OPENING' => ConversationPhase::RAPPORT,
            'WEAK_OPENING' => ConversationPhase::RAPPORT,
            'ACTIVE_LISTENING' => ConversationPhase::RAPPORT,
            'RELEVANT_FOLLOW_UP' => ConversationPhase::DISCOVERY,
            'CONCERN_DISCOVERED' => ConversationPhase::DISCOVERY,
            'APPROPRIATE_NEXT_STEP' => ConversationPhase::DISCOVERY,
            'CLEAR_EXPLANATION' => ConversationPhase::EXPLANATION,
        ],
        ConversationPhase::RAPPORT->value => [
            'RELEVANT_FOLLOW_UP' => ConversationPhase::DISCOVERY,
            'CONCERN_DISCOVERED' => ConversationPhase::DISCOVERY,
            'CLEAR_EXPLANATION' => ConversationPhase::EXPLANATION,
            'APPROPRIATE_NEXT_STEP' => ConversationPhase::DISCOVERY,
            'OBJECTION_TRIGGERED' => ConversationPhase::OBJECTION_HANDLING,
        ],
        ConversationPhase::DISCOVERY->value => [
            'RELEVANT_FOLLOW_UP' => ConversationPhase::NEED_EXPLORATION,
            'CONCERN_DISCOVERED' => ConversationPhase::NEED_EXPLORATION,
            'CLEAR_EXPLANATION' => ConversationPhase::EXPLANATION,
            'OBJECTION_TRIGGERED' => ConversationPhase::OBJECTION_HANDLING,
            'APPROPRIATE_NEXT_STEP' => ConversationPhase::NEED_EXPLORATION,
        ],
        ConversationPhase::NEED_EXPLORATION->value => [
            'CLEAR_EXPLANATION' => ConversationPhase::EXPLANATION,
            'OBJECTION_TRIGGERED' => ConversationPhase::OBJECTION_HANDLING,
            'APPROPRIATE_NEXT_STEP' => ConversationPhase::EXPLANATION,
            'CONCERN_DISCOVERED' => ConversationPhase::DISCOVERY,
            'RELEVANT_FOLLOW_UP' => ConversationPhase::DISCOVERY,
        ],
        ConversationPhase::EXPLANATION->value => [
            'OBJECTION_TRIGGERED' => ConversationPhase::OBJECTION_HANDLING,
            'CONCERN_DISCOVERED' => ConversationPhase::DISCOVERY,
            'RELEVANT_FOLLOW_UP' => ConversationPhase::DISCOVERY,
            'APPROPRIATE_NEXT_STEP' => ConversationPhase::COMMITMENT,
            'OBJECTION_RESOLVED_CANDIDATE' => ConversationPhase::COMMITMENT,
        ],
        ConversationPhase::OBJECTION_HANDLING->value => [
            'OBJECTION_RESOLVED_CANDIDATE' => ConversationPhase::COMMITMENT,
            'CONCERN_DISCOVERED' => ConversationPhase::DISCOVERY,
            'RELEVANT_FOLLOW_UP' => ConversationPhase::DISCOVERY,
            'APPROPRIATE_NEXT_STEP' => ConversationPhase::COMMITMENT,
            'CLEAR_EXPLANATION' => ConversationPhase::EXPLANATION,
        ],
        ConversationPhase::COMMITMENT->value => [
            'APPROPRIATE_NEXT_STEP' => ConversationPhase::CLOSING,
            'OBJECTION_RESOLVED_CANDIDATE' => ConversationPhase::COMMITMENT,
            'CONCERN_DISCOVERED' => ConversationPhase::DISCOVERY,
            'RELEVANT_FOLLOW_UP' => ConversationPhase::DISCOVERY,
            'OBJECTION_TRIGGERED' => ConversationPhase::OBJECTION_HANDLING,
        ],
        ConversationPhase::CLOSING->value => [
            'APPROPRIATE_NEXT_STEP' => ConversationPhase::ENDING,
            'CONCERN_DISCOVERED' => ConversationPhase::DISCOVERY,
            'RELEVANT_FOLLOW_UP' => ConversationPhase::DISCOVERY,
            'OBJECTION_TRIGGERED' => ConversationPhase::OBJECTION_HANDLING,
        ],
        ConversationPhase::ENDING->value => [],
    ];

    private int $transitionCount = 0;
    private const int MAX_TRANSITIONS = 50;
    private ConversationPhase $currentPhase;

    public function __construct(?ConversationPhase $initialPhase = null)
    {
        $this->currentPhase = $initialPhase ?? ConversationPhase::OPENING;
    }

    public function getCurrentPhase(): ConversationPhase
    {
        return $this->currentPhase;
    }

    public function processEvent(RoleplayEvent $event): ?ConversationPhaseTransition
    {
        if ($this->transitionCount >= self::MAX_TRANSITIONS) {
            return null;
        }

        $eventType = $event->type->value;

        $rules = self::TRANSITION_RULES[$this->currentPhase->value] ?? null;

        if ($rules === null) {
            return null;
        }

        $targetPhase = $rules[$eventType] ?? null;

        if ($targetPhase === null) {
            return null;
        }

        $targetIndex = self::PHASE_ORDER[$targetPhase->value];
        $currentIndex = self::PHASE_ORDER[$this->currentPhase->value];
        $isBackward = $targetIndex < $currentIndex;

        $fromPhase = $this->currentPhase;
        $this->currentPhase = $targetPhase;
        $this->transitionCount++;

        return new ConversationPhaseTransition(
            fromPhase: $fromPhase,
            toPhase: $targetPhase,
            triggeredBy: $event->type,
            accepted: true,
            prematureClosing: $this->isPrematureClosing($eventType, $fromPhase),
        );
    }

    public function isInEarlyPhase(): bool
    {
        return isset(self::PREMATURE_CLOSING_PHASES[$this->currentPhase->value]);
    }

    public function isPrematureClosingEvent(RoleplayEvent $event): bool
    {
        if ($event->type !== RoleplayEventType::AGGRESSIVE_CLOSING) {
            return false;
        }

        return isset(self::PREMATURE_CLOSING_PHASES[$this->currentPhase->value]);
    }

    public function transitionToEnding(): ConversationPhaseTransition
    {
        $fromPhase = $this->currentPhase;
        $this->currentPhase = ConversationPhase::ENDING;
        $this->transitionCount++;

        return new ConversationPhaseTransition(
            fromPhase: $fromPhase,
            toPhase: ConversationPhase::ENDING,
            triggeredBy: RoleplayEventType::APPROPRIATE_NEXT_STEP,
            accepted: true,
        );
    }

    public function restorePhase(ConversationPhase $phase): void
    {
        $this->currentPhase = $phase;
    }

    public function reset(?ConversationPhase $initialPhase = null): void
    {
        $this->currentPhase = $initialPhase ?? ConversationPhase::OPENING;
        $this->transitionCount = 0;
    }

    public function toArray(): array
    {
        return [
            'current_phase' => $this->currentPhase->value,
            'transition_count' => $this->transitionCount,
        ];
    }

    private function isPrematureClosing(string $eventType, ConversationPhase $phase): bool
    {
        if ($eventType !== 'AGGRESSIVE_CLOSING') {
            return false;
        }

        return isset(self::PREMATURE_CLOSING_PHASES[$phase->value]);
    }
}
