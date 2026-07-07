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

    public function withPositiveDiminishing(float $multiplier): self
    {
        return new self(
            trustDelta: $this->trustDelta > 0 ? (int)round($this->trustDelta * $multiplier) : $this->trustDelta,
            interestDelta: $this->interestDelta > 0 ? (int)round($this->interestDelta * $multiplier) : $this->interestDelta,
            confusionDelta: $this->confusionDelta < 0 ? (int)round($this->confusionDelta * $multiplier) : $this->confusionDelta,
            anxietyDelta: $this->anxietyDelta < 0 ? (int)round($this->anxietyDelta * $multiplier) : $this->anxietyDelta,
            irritationDelta: $this->irritationDelta < 0 ? (int)round($this->irritationDelta * $multiplier) : $this->irritationDelta,
            pressurePerceptionDelta: $this->pressurePerceptionDelta < 0 ? (int)round($this->pressurePerceptionDelta * $multiplier) : $this->pressurePerceptionDelta,
            engagementDelta: $this->engagementDelta > 0 ? (int)round($this->engagementDelta * $multiplier) : $this->engagementDelta,
        );
    }

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
