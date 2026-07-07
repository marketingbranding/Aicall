<?php

namespace App\Services\Director;

readonly class StateTransition
{
    public function __construct(
        public int $trustDelta = 0,
        public int $interestDelta = 0,
        public int $confusionDelta = 0,
        public int $anxietyDelta = 0,
        public int $irritationDelta = 0,
        public int $pressurePerceptionDelta = 0,
        public int $engagementDelta = 0,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'trust_delta' => $this->trustDelta,
            'interest_delta' => $this->interestDelta,
            'confusion_delta' => $this->confusionDelta,
            'anxiety_delta' => $this->anxietyDelta,
            'irritation_delta' => $this->irritationDelta,
            'pressure_perception_delta' => $this->pressurePerceptionDelta,
            'engagement_delta' => $this->engagementDelta,
        ], fn(int $v) => $v !== 0);
    }
}
