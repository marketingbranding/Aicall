<?php

namespace App\Services\Director;

readonly class DirectorEngineResult
{
    /** @var ObjectionTransition[] */
    public array $objectionTransitions;

    public function __construct(
        public DirectorState $state,
        public StateTransition $appliedTransition,
        public bool $accepted,
        public ?string $rejectionReason = null,
        ?array $objectionTransitions = null,
    ) {
        $this->objectionTransitions = $objectionTransitions ?? [];
    }

    public function toArray(): array
    {
        return [
            'state' => $this->state->toArray(),
            'applied_transition' => $this->appliedTransition->toArray(),
            'accepted' => $this->accepted,
            'rejection_reason' => $this->rejectionReason,
            'objection_transitions' => array_map(
                fn(ObjectionTransition $t) => $t->toArray(),
                $this->objectionTransitions,
            ),
        ];
    }
}
