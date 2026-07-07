<?php

namespace App\Services\Snapshots;

readonly class DifficultySnapshot
{
    public function __construct(
        public string $level,
        public bool $isCustom,
        public float $trustGainMultiplier,
        public float $trustLossMultiplier,
        public int $disclosureResistance,
        public int $objectionPersistence,
        public int $irritationSensitivity,
        public int $weakExplanationTolerance,
        public int $closingResistance,
        public int $boundaryPersistence,
    ) {}

    public function toArray(): array
    {
        return [
            'level' => $this->level,
            'is_custom' => $this->isCustom,
            'trust_gain_multiplier' => $this->trustGainMultiplier,
            'trust_loss_multiplier' => $this->trustLossMultiplier,
            'disclosure_resistance' => $this->disclosureResistance,
            'objection_persistence' => $this->objectionPersistence,
            'irritation_sensitivity' => $this->irritationSensitivity,
            'weak_explanation_tolerance' => $this->weakExplanationTolerance,
            'closing_resistance' => $this->closingResistance,
            'boundary_persistence' => $this->boundaryPersistence,
        ];
    }
}
