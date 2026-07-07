<?php

namespace App\Services\Director;

readonly class DirectorEngineResult
{
    public function __construct(
        public DirectorState $state,
        public StateTransition $appliedTransition,
        public bool $accepted,
        public ?string $rejectionReason = null,
    ) {}
}
