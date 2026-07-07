<?php

namespace App\Services\Director;

class DirectorState
{
    public function __construct(
        private int $trust = 50,
        private int $interest = 50,
        private int $confusion = 10,
        private int $anxiety = 30,
        private int $irritation = 10,
        private int $pressurePerception = 10,
        private int $engagement = 50,
    ) {
        $this->trust = self::clamp($trust);
        $this->interest = self::clamp($interest);
        $this->confusion = self::clamp($confusion);
        $this->anxiety = self::clamp($anxiety);
        $this->irritation = self::clamp($irritation);
        $this->pressurePerception = self::clamp($pressurePerception);
        $this->engagement = self::clamp($engagement);
    }

    public static function default(): self
    {
        return new self();
    }

    public function apply(StateTransition $delta): self
    {
        return new self(
            trust: $this->trust + $delta->trustDelta,
            interest: $this->interest + $delta->interestDelta,
            confusion: $this->confusion + $delta->confusionDelta,
            anxiety: $this->anxiety + $delta->anxietyDelta,
            irritation: $this->irritation + $delta->irritationDelta,
            pressurePerception: $this->pressurePerception + $delta->pressurePerceptionDelta,
            engagement: $this->engagement + $delta->engagementDelta,
        );
    }

    public function getTrust(): int { return $this->trust; }
    public function getInterest(): int { return $this->interest; }
    public function getConfusion(): int { return $this->confusion; }
    public function getAnxiety(): int { return $this->anxiety; }
    public function getIrritation(): int { return $this->irritation; }
    public function getPressurePerception(): int { return $this->pressurePerception; }
    public function getEngagement(): int { return $this->engagement; }

    public function toArray(): array
    {
        return [
            'trust' => $this->trust,
            'interest' => $this->interest,
            'confusion' => $this->confusion,
            'anxiety' => $this->anxiety,
            'irritation' => $this->irritation,
            'pressure_perception' => $this->pressurePerception,
            'engagement' => $this->engagement,
        ];
    }

    private static function clamp(int $value): int
    {
        return max(0, min(100, $value));
    }
}
