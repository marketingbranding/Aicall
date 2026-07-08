<?php

namespace App\Services\Director;

class BoundaryStateMachine
{
    private const int BASE_COOLDOWN_EVENTS = 3;

    private const array TRANSITION_RULES = [
        BoundaryState::NOT_TESTED->value => [
            'CUSTOMER_BOUNDARY_TEST' => BoundaryState::MILD_TEST_OCCURRED,
            'SIGNIFICANT_BOUNDARY_VIOLATION' => BoundaryState::SIGNIFICANT_VIOLATION,
        ],
        BoundaryState::MILD_TEST_OCCURRED->value => [
            'SALESPERSON_PARTICIPATED_PERSONALLY' => BoundaryState::SALESPERSON_PARTICIPATED,
            'INDIRECT_REDIRECTION' => BoundaryState::INDIRECTLY_REDIRECTED,
            'CLEAR_PROFESSIONAL_REDIRECTION' => BoundaryState::CLEAR_BOUNDARY_ESTABLISHED,
            'EXPLICIT_BOUNDARY_SET' => BoundaryState::CLEAR_BOUNDARY_ESTABLISHED,
            'CUSTOMER_RESPECTED_BOUNDARY' => BoundaryState::CUSTOMER_RESPECTED_BOUNDARY,
            'CUSTOMER_REPEATED_BOUNDARY_TEST' => BoundaryState::CUSTOMER_RETESTED_BOUNDARY,
            'SIGNIFICANT_BOUNDARY_VIOLATION' => BoundaryState::SIGNIFICANT_VIOLATION,
            'CUSTOMER_BOUNDARY_TEST' => BoundaryState::MILD_TEST_OCCURRED,
        ],
        BoundaryState::SALESPERSON_PARTICIPATED->value => [
            'CLEAR_PROFESSIONAL_REDIRECTION' => BoundaryState::CLEAR_BOUNDARY_ESTABLISHED,
            'EXPLICIT_BOUNDARY_SET' => BoundaryState::CLEAR_BOUNDARY_ESTABLISHED,
            'CUSTOMER_REPEATED_BOUNDARY_TEST' => BoundaryState::CUSTOMER_RETESTED_BOUNDARY,
            'SIGNIFICANT_BOUNDARY_VIOLATION' => BoundaryState::SIGNIFICANT_VIOLATION,
            'CUSTOMER_BOUNDARY_TEST' => BoundaryState::MILD_TEST_OCCURRED,
        ],
        BoundaryState::INDIRECTLY_REDIRECTED->value => [
            'CLEAR_PROFESSIONAL_REDIRECTION' => BoundaryState::CLEAR_BOUNDARY_ESTABLISHED,
            'EXPLICIT_BOUNDARY_SET' => BoundaryState::CLEAR_BOUNDARY_ESTABLISHED,
            'CUSTOMER_RESPECTED_BOUNDARY' => BoundaryState::CUSTOMER_RESPECTED_BOUNDARY,
            'CUSTOMER_REPEATED_BOUNDARY_TEST' => BoundaryState::CUSTOMER_RETESTED_BOUNDARY,
            'SIGNIFICANT_BOUNDARY_VIOLATION' => BoundaryState::SIGNIFICANT_VIOLATION,
            'CUSTOMER_BOUNDARY_TEST' => BoundaryState::MILD_TEST_OCCURRED,
        ],
        BoundaryState::CLEAR_BOUNDARY_ESTABLISHED->value => [
            'CUSTOMER_RESPECTED_BOUNDARY' => BoundaryState::CUSTOMER_RESPECTED_BOUNDARY,
            'CUSTOMER_REPEATED_BOUNDARY_TEST' => BoundaryState::CUSTOMER_RETESTED_BOUNDARY,
            'SIGNIFICANT_BOUNDARY_VIOLATION' => BoundaryState::SIGNIFICANT_VIOLATION,
            'CUSTOMER_BOUNDARY_TEST' => BoundaryState::MILD_TEST_OCCURRED,
        ],
        BoundaryState::CUSTOMER_RESPECTED_BOUNDARY->value => [
            'CUSTOMER_REPEATED_BOUNDARY_TEST' => BoundaryState::CUSTOMER_RETESTED_BOUNDARY,
            'SIGNIFICANT_BOUNDARY_VIOLATION' => BoundaryState::SIGNIFICANT_VIOLATION,
            'CUSTOMER_BOUNDARY_TEST' => BoundaryState::MILD_TEST_OCCURRED,
        ],
        BoundaryState::CUSTOMER_RETESTED_BOUNDARY->value => [
            'CLEAR_PROFESSIONAL_REDIRECTION' => BoundaryState::CLEAR_BOUNDARY_ESTABLISHED,
            'EXPLICIT_BOUNDARY_SET' => BoundaryState::CLEAR_BOUNDARY_ESTABLISHED,
            'CUSTOMER_REPEATED_BOUNDARY_TEST' => BoundaryState::CUSTOMER_RETESTED_BOUNDARY,
            'SIGNIFICANT_BOUNDARY_VIOLATION' => BoundaryState::SIGNIFICANT_VIOLATION,
            'SALESPERSON_PARTICIPATED_PERSONALLY' => BoundaryState::SALESPERSON_PARTICIPATED,
            'CUSTOMER_BOUNDARY_TEST' => BoundaryState::MILD_TEST_OCCURRED,
        ],
        BoundaryState::SIGNIFICANT_VIOLATION->value => [
            'SIGNIFICANT_BOUNDARY_VIOLATION' => BoundaryState::PROFESSIONAL_TERMINATION_ELIGIBLE,
            'CLEAR_PROFESSIONAL_REDIRECTION' => BoundaryState::CLEAR_BOUNDARY_ESTABLISHED,
            'CUSTOMER_RESPECTED_BOUNDARY' => BoundaryState::CUSTOMER_RESPECTED_BOUNDARY,
            'CUSTOMER_REPEATED_BOUNDARY_TEST' => BoundaryState::CUSTOMER_RETESTED_BOUNDARY,
            'CUSTOMER_BOUNDARY_TEST' => BoundaryState::MILD_TEST_OCCURRED,
        ],
        BoundaryState::PROFESSIONAL_TERMINATION_ELIGIBLE->value => [],
    ];

    private const array COOLDOWN_EVENT_TYPES = [
        'CUSTOMER_BOUNDARY_TEST' => true,
        'CUSTOMER_REPEATED_BOUNDARY_TEST' => true,
    ];

    private BoundaryState $currentState = BoundaryState::NOT_TESTED;
    private ?string $lastBoundaryTestType = null;
    private int $eventsSinceLastBoundaryTest = 0;
    private int $transitionCount = 0;
    private int $boundaryPersistence = 50;
    private const int MAX_TRANSITIONS = 50;

    public function __construct(
        private int $respectForBoundaries = 50,
        private int $persistenceAfterRedirection = 50,
    ) {}

    public function getCurrentState(): BoundaryState
    {
        return $this->currentState;
    }

    public function setRespectForBoundaries(int $value): void
    {
        $this->respectForBoundaries = max(0, min(100, $value));
    }

    public function setPersistenceAfterRedirection(int $value): void
    {
        $this->persistenceAfterRedirection = max(0, min(100, $value));
    }

    public function getRespectForBoundaries(): int
    {
        return $this->respectForBoundaries;
    }

    public function getPersistenceAfterRedirection(): int
    {
        return $this->persistenceAfterRedirection;
    }

    public function processEvent(RoleplayEvent $event, int $currentStateTrust = 50): ?BoundaryTransition
    {
        if ($this->transitionCount >= self::MAX_TRANSITIONS) {
            return null;
        }

        $eventType = $event->type->value;

        $rules = self::TRANSITION_RULES[$this->currentState->value] ?? null;

        if ($rules === null) {
            return null;
        }

        $this->eventsSinceLastBoundaryTest++;

        if (isset(self::COOLDOWN_EVENT_TYPES[$eventType])) {
            if ($this->isInCooldown($eventType)) {
                return new BoundaryTransition(
                    fromState: $this->currentState,
                    toState: $this->currentState,
                    triggeredBy: $event->type,
                    accepted: false,
                    rejectionReason: 'Boundary test in cooldown',
                );
            }
        }

        $targetState = $rules[$eventType] ?? null;

        if ($targetState === null) {
            return new BoundaryTransition(
                fromState: $this->currentState,
                toState: $this->currentState,
                triggeredBy: $event->type,
                accepted: false,
                rejectionReason: "Event {$eventType} does not trigger transition from {$this->currentState->value}",
            );
        }

        $fromState = $this->currentState;
        $this->currentState = $targetState;
        $this->transitionCount++;

        if (isset(self::COOLDOWN_EVENT_TYPES[$eventType])) {
            $this->lastBoundaryTestType = $eventType;
            $this->eventsSinceLastBoundaryTest = 0;
        }

        return new BoundaryTransition(
            fromState: $fromState,
            toState: $targetState,
            triggeredBy: $event->type,
            accepted: true,
            directorNote: $this->buildDirectorNote($fromState, $targetState, $eventType, $currentStateTrust),
        );
    }

    public function setBoundaryPersistence(int $value): void
    {
        $this->boundaryPersistence = max(0, min(100, $value));
    }

    public function getBoundaryPersistence(): int
    {
        return $this->boundaryPersistence;
    }

    private function isInCooldown(string $eventType): bool
    {
        if ($this->lastBoundaryTestType === null) {
            return false;
        }

        $threshold = $this->getCooldownEvents();
        return $this->lastBoundaryTestType === $eventType && $this->eventsSinceLastBoundaryTest < $threshold;
    }

    private function getCooldownEvents(): int
    {
        $cooldown = (int) round(5 - ($this->boundaryPersistence * 0.04));
        return max(1, min(5, $cooldown));
    }

    public function getCooldownRemaining(): int
    {
        if ($this->lastBoundaryTestType === null) {
            return 0;
        }

        $threshold = $this->getCooldownEvents();
        return max(0, $threshold - $this->eventsSinceLastBoundaryTest);
    }

    public function restoreState(BoundaryState $state): void
    {
        $this->currentState = $state;
    }

    public function reset(): void
    {
        $this->currentState = BoundaryState::NOT_TESTED;
        $this->lastBoundaryTestType = null;
        $this->eventsSinceLastBoundaryTest = 0;
        $this->transitionCount = 0;
    }

    public function toArray(): array
    {
        return [
            'current_state' => $this->currentState->value,
            'respect_for_boundaries' => $this->respectForBoundaries,
            'persistence_after_redirection' => $this->persistenceAfterRedirection,
            'boundary_persistence' => $this->boundaryPersistence,
            'transition_count' => $this->transitionCount,
            'cooldown_remaining' => $this->getCooldownRemaining(),
        ];
    }

    private function buildDirectorNote(BoundaryState $from, BoundaryState $to, string $eventType, int $currentTrust): ?string
    {
        return match (true) {
            $from === BoundaryState::NOT_TESTED && $to === BoundaryState::MILD_TEST_OCCURRED =>
                'The customer tested a personal boundary. Respond professionally without over-sharing.',

            $from === BoundaryState::MILD_TEST_OCCURRED && $to === BoundaryState::SALESPERSON_PARTICIPATED =>
                'The salesperson engaged personally. The customer may perceive this as permission to continue.',

            $from === BoundaryState::MILD_TEST_OCCURRED && $to === BoundaryState::INDIRECTLY_REDIRECTED =>
                'The salesperson redirected indirectly. The customer may test again depending on persistence.',

            $from === BoundaryState::MILD_TEST_OCCURRED && $to === BoundaryState::CLEAR_BOUNDARY_ESTABLISHED =>
                'A clear professional boundary has been set. The customer should respect it.',

            $from === BoundaryState::SALESPERSON_PARTICIPATED && $to === BoundaryState::CLEAR_BOUNDARY_ESTABLISHED =>
                'The salesperson re-established a professional boundary after personal engagement.',

            $from === BoundaryState::INDIRECTLY_REDIRECTED && $to === BoundaryState::CLEAR_BOUNDARY_ESTABLISHED =>
                'The salesperson made the redirection explicit. The boundary is now clear.',

            $to === BoundaryState::CUSTOMER_RESPECTED_BOUNDARY =>
                'The customer respected the professional boundary.',

            $to === BoundaryState::CUSTOMER_RETESTED_BOUNDARY =>
                $this->buildRetestNote(),

            $from !== BoundaryState::SIGNIFICANT_VIOLATION && $to === BoundaryState::SIGNIFICANT_VIOLATION =>
                'A significant boundary violation occurred. Professional handling is critical.',

            $from === BoundaryState::SIGNIFICANT_VIOLATION && $to === BoundaryState::PROFESSIONAL_TERMINATION_ELIGIBLE =>
                'Repeated significant violations make professional termination eligible.',

            $from === BoundaryState::SIGNIFICANT_VIOLATION && $to === BoundaryState::CLEAR_BOUNDARY_ESTABLISHED =>
                'The salesperson re-established boundaries after a significant violation.',

            default => null,
        };
    }

    private function buildRetestNote(): string
    {
        if ($this->persistenceAfterRedirection >= 70) {
            return 'The customer persistently tests boundaries despite redirection. Remind the salesperson to stay firm.';
        }

        if ($this->persistenceAfterRedirection >= 40) {
            return 'The customer retested a boundary after redirection.';
        }

        return 'A minor boundary retest occurred.';
    }
}
