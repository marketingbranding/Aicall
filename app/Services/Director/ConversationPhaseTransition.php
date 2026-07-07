<?php

namespace App\Services\Director;

readonly class ConversationPhaseTransition
{
    public function __construct(
        public ConversationPhase $fromPhase,
        public ConversationPhase $toPhase,
        public RoleplayEventType $triggeredBy,
        public bool $accepted,
        public bool $prematureClosing = false,
        public ?string $rejectionReason = null,
    ) {}

    public function toArray(): array
    {
        return [
            'from_phase' => $this->fromPhase->value,
            'to_phase' => $this->toPhase->value,
            'triggered_by' => $this->triggeredBy->value,
            'accepted' => $this->accepted,
            'premature_closing' => $this->prematureClosing,
            'rejection_reason' => $this->rejectionReason,
        ];
    }
}
