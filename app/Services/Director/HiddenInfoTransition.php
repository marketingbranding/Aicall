<?php

namespace App\Services\Director;

readonly class HiddenInfoTransition
{
    public function __construct(
        public string $key,
        public HiddenInfoState $fromState,
        public HiddenInfoState $toState,
        public RoleplayEventType $triggeredBy,
        public bool $accepted,
        public ?string $rejectionReason = null,
        public ?string $directorNote = null,
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'from_state' => $this->fromState->value,
            'to_state' => $this->toState->value,
            'triggered_by' => $this->triggeredBy->value,
            'accepted' => $this->accepted,
            'rejection_reason' => $this->rejectionReason,
            'director_note' => $this->directorNote,
        ];
    }
}
