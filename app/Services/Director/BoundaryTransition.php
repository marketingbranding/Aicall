<?php

namespace App\Services\Director;

readonly class BoundaryTransition
{
    public function __construct(
        public BoundaryState $fromState,
        public BoundaryState $toState,
        public RoleplayEventType $triggeredBy,
        public bool $accepted,
        public ?string $rejectionReason = null,
        public ?string $directorNote = null,
    ) {}

    public function toArray(): array
    {
        return [
            'from_state' => $this->fromState->value,
            'to_state' => $this->toState->value,
            'triggered_by' => $this->triggeredBy->value,
            'accepted' => $this->accepted,
            'rejection_reason' => $this->rejectionReason,
            'director_note' => $this->directorNote,
        ];
    }
}
