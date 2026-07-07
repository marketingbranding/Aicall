<?php

namespace App\Services\Director;

readonly class DirectorEngineResult
{
    /** @var ObjectionTransition[] */
    public array $objectionTransitions;

    /** @var HiddenInfoTransition[] */
    public array $hiddenInfoTransitions;

    public function __construct(
        public DirectorState $state,
        public StateTransition $appliedTransition,
        public bool $accepted,
        public ?string $rejectionReason = null,
        ?array $objectionTransitions = null,
        ?array $hiddenInfoTransitions = null,
    ) {
        $this->objectionTransitions = $objectionTransitions ?? [];
        $this->hiddenInfoTransitions = $hiddenInfoTransitions ?? [];
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
            'hidden_info_transitions' => array_map(
                fn(HiddenInfoTransition $t) => $t->toArray(),
                $this->hiddenInfoTransitions,
            ),
        ];
    }
}
