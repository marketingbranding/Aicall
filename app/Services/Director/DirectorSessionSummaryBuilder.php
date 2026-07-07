<?php

namespace App\Services\Director;

class DirectorSessionSummaryBuilder
{
    /** @var DirectorSessionEvent[] */
    private array $events = [];

    private ?string $aiEndingReason = null;
    private ?string $aiEndingNote = null;

    public function recordObjectionTransition(ObjectionTransition $transition, int $turn): void
    {
        if (!$transition->accepted || $transition->fromState === $transition->toState) {
            return;
        }

        $description = $transition->directorNote
            ?? $this->defaultObjectionDescription($transition);

        $this->events[] = new DirectorSessionEvent(
            category: 'objection',
            description: $description,
            turn: $turn,
        );
    }

    public function recordHiddenInfoTransition(HiddenInfoTransition $transition, int $turn): void
    {
        if (!$transition->accepted || $transition->fromState === $transition->toState) {
            return;
        }

        $description = $transition->directorNote
            ?? $this->defaultHiddenInfoDescription($transition);

        $this->events[] = new DirectorSessionEvent(
            category: 'hidden_info',
            description: $description,
            turn: $turn,
        );
    }

    public function recordBoundaryTransition(BoundaryTransition $transition, int $turn): void
    {
        if (!$transition->accepted || $transition->fromState === $transition->toState) {
            return;
        }

        $description = $transition->directorNote
            ?? $this->defaultBoundaryDescription($transition);

        $this->events[] = new DirectorSessionEvent(
            category: 'boundary',
            description: $description,
            turn: $turn,
        );
    }

    public function recordPhaseTransition(ConversationPhaseTransition $transition, int $turn): void
    {
        if (!$transition->accepted) {
            return;
        }

        if ($transition->prematureClosing) {
            $this->events[] = new DirectorSessionEvent(
                category: 'phase_change',
                description: "Premature closing attempt: salesperson tried closing during {$transition->fromPhase->value} phase.",
                turn: $turn,
            );
        }

        if ($transition->fromPhase === $transition->toPhase) {
            return;
        }

        $this->events[] = new DirectorSessionEvent(
            category: 'phase_change',
            description: "Phase changed from {$transition->fromPhase->value} to {$transition->toPhase->value}.",
            turn: $turn,
        );
    }

    /** @param DirectorNote[] $thresholdNotes */
    public function recordStateThresholds(array $thresholdNotes, int $turn): void
    {
        foreach ($thresholdNotes as $note) {
            if ($note->category !== 'state_threshold') {
                continue;
            }

            $this->events[] = new DirectorSessionEvent(
                category: 'state_threshold',
                description: $note->text,
                turn: $turn,
            );
        }
    }

    public function recordAiEndEligibility(AiEndEligibilityResult $result): void
    {
        if ($result->eligible) {
            $this->aiEndingReason = $result->reasonCode;
            $this->aiEndingNote = $result->directorNote;
        }
    }

    public function build(): DirectorSessionSummary
    {
        $sorted = $this->events;
        usort($sorted, fn(DirectorSessionEvent $a, DirectorSessionEvent $b) => ($a->turn ?? 0) <=> ($b->turn ?? 0));

        return new DirectorSessionSummary(
            events: $sorted,
            aiEndingReason: $this->aiEndingReason,
            aiEndingNote: $this->aiEndingNote,
        );
    }

    public function reset(): void
    {
        $this->events = [];
        $this->aiEndingReason = null;
        $this->aiEndingNote = null;
    }

    private function defaultObjectionDescription(ObjectionTransition $t): string
    {
        return match (true) {
            $t->fromState === ObjectionState::DORMANT && $t->toState === ObjectionState::ACTIVE_VISIBLE =>
                "Concern {$t->objectionKey} became visible.",
            $t->fromState === ObjectionState::DORMANT && $t->toState === ObjectionState::ACTIVE_HIDDEN =>
                "Concern {$t->objectionKey} became active but remains hidden.",
            $t->fromState === ObjectionState::ACTIVE_HIDDEN && $t->toState === ObjectionState::ACTIVE_VISIBLE =>
                "Concern {$t->objectionKey} was revealed by the customer.",
            $t->fromState === ObjectionState::ACTIVE_VISIBLE && $t->toState === ObjectionState::ACKNOWLEDGED =>
                "Concern {$t->objectionKey} was acknowledged by salesperson.",
            $t->fromState === ObjectionState::ACKNOWLEDGED && $t->toState === ObjectionState::PARTIALLY_RESOLVED =>
                "Concern {$t->objectionKey} was partially resolved.",
            $t->fromState === ObjectionState::PARTIALLY_RESOLVED && $t->toState === ObjectionState::RESOLVED =>
                "Concern {$t->objectionKey} was resolved.",
            $t->fromState === ObjectionState::RESOLVED && $t->toState === ObjectionState::REACTIVATED =>
                "Concern {$t->objectionKey} was reactivated.",
            $t->fromState === ObjectionState::PARTIALLY_RESOLVED && $t->toState === ObjectionState::REACTIVATED =>
                "Concern {$t->objectionKey} intensified again.",
            $t->fromState === ObjectionState::ACKNOWLEDGED && $t->toState === ObjectionState::ACTIVE_VISIBLE =>
                "Concern {$t->objectionKey} became active again after dismissal.",
            default => "Concern {$t->objectionKey} state changed from {$t->fromState->value} to {$t->toState->value}.",
        };
    }

    private function defaultHiddenInfoDescription(HiddenInfoTransition $t): string
    {
        return match (true) {
            $t->fromState === HiddenInfoState::LOCKED && $t->toState === HiddenInfoState::ELIGIBLE =>
                "Hidden information {$t->key} became eligible for disclosure.",
            $t->fromState === HiddenInfoState::ELIGIBLE && $t->toState === HiddenInfoState::DISCLOSED_PARTIAL =>
                "Customer partially disclosed hidden information: {$t->key}.",
            $t->fromState === HiddenInfoState::DISCLOSED_PARTIAL && $t->toState === HiddenInfoState::DISCLOSED_FULL =>
                "Customer fully disclosed hidden information: {$t->key}.",
            default => "Hidden information {$t->key} state changed from {$t->fromState->value} to {$t->toState->value}.",
        };
    }

    private function defaultBoundaryDescription(BoundaryTransition $t): string
    {
        return match (true) {
            $t->fromState === BoundaryState::NOT_TESTED && $t->toState === BoundaryState::MILD_TEST_OCCURRED =>
                'Customer tested a personal boundary.',
            $t->toState === BoundaryState::SALESPERSON_PARTICIPATED =>
                'Salesperson participated personally in boundary conversation.',
            $t->toState === BoundaryState::INDIRECTLY_REDIRECTED =>
                'Salesperson indirectly redirected boundary conversation.',
            $t->toState === BoundaryState::CLEAR_BOUNDARY_ESTABLISHED =>
                'A clear professional boundary was established.',
            $t->toState === BoundaryState::CUSTOMER_RESPECTED_BOUNDARY =>
                'Customer respected the professional boundary.',
            $t->toState === BoundaryState::CUSTOMER_RETESTED_BOUNDARY =>
                'Customer retested a boundary after redirection.',
            $t->toState === BoundaryState::SIGNIFICANT_VIOLATION =>
                'A significant boundary violation occurred.',
            $t->fromState === BoundaryState::SIGNIFICANT_VIOLATION && $t->toState === BoundaryState::PROFESSIONAL_TERMINATION_ELIGIBLE =>
                'Repeated violations made professional termination eligible.',
            default => "Boundary state changed from {$t->fromState->value} to {$t->toState->value}.",
        };
    }
}
