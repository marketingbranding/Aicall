<?php

namespace App\Services\Director;

readonly class DifficultyModifier
{
    private const float MIN_MULTIPLIER = 0.1;
    private const float MAX_MULTIPLIER = 5.0;
    private const int MIN_INT = 0;
    private const int MAX_INT = 100;

    private const array DEFAULTS = [
        DifficultyLevel::BEGINNER->value => [
            'trustGainMultiplier' => 1.5,
            'trustLossMultiplier' => 0.5,
            'disclosureResistance' => 20,
            'objectionPersistence' => 20,
            'irritationSensitivity' => 20,
            'weakExplanationTolerance' => 80,
            'closingResistance' => 20,
            'boundaryPersistence' => 20,
        ],
        DifficultyLevel::NORMAL->value => [
            'trustGainMultiplier' => 1.0,
            'trustLossMultiplier' => 1.0,
            'disclosureResistance' => 50,
            'objectionPersistence' => 50,
            'irritationSensitivity' => 50,
            'weakExplanationTolerance' => 50,
            'closingResistance' => 50,
            'boundaryPersistence' => 50,
        ],
        DifficultyLevel::DIFFICULT->value => [
            'trustGainMultiplier' => 0.75,
            'trustLossMultiplier' => 1.25,
            'disclosureResistance' => 65,
            'objectionPersistence' => 65,
            'irritationSensitivity' => 65,
            'weakExplanationTolerance' => 35,
            'closingResistance' => 65,
            'boundaryPersistence' => 65,
        ],
        DifficultyLevel::EXPERT->value => [
            'trustGainMultiplier' => 0.5,
            'trustLossMultiplier' => 1.5,
            'disclosureResistance' => 85,
            'objectionPersistence' => 85,
            'irritationSensitivity' => 85,
            'weakExplanationTolerance' => 15,
            'closingResistance' => 85,
            'boundaryPersistence' => 85,
        ],
    ];

    public float $trustGainMultiplier;
    public float $trustLossMultiplier;
    public int $disclosureResistance;
    public int $objectionPersistence;
    public int $irritationSensitivity;
    public int $weakExplanationTolerance;
    public int $closingResistance;
    public int $boundaryPersistence;

    public function __construct(
        float $trustGainMultiplier = 1.0,
        float $trustLossMultiplier = 1.0,
        int $disclosureResistance = 50,
        int $objectionPersistence = 50,
        int $irritationSensitivity = 50,
        int $weakExplanationTolerance = 50,
        int $closingResistance = 50,
        int $boundaryPersistence = 50,
    ) {
        $this->trustGainMultiplier = self::clampFloat($trustGainMultiplier, self::MIN_MULTIPLIER, self::MAX_MULTIPLIER);
        $this->trustLossMultiplier = self::clampFloat($trustLossMultiplier, self::MIN_MULTIPLIER, self::MAX_MULTIPLIER);
        $this->disclosureResistance = self::clampInt($disclosureResistance);
        $this->objectionPersistence = self::clampInt($objectionPersistence);
        $this->irritationSensitivity = self::clampInt($irritationSensitivity);
        $this->weakExplanationTolerance = self::clampInt($weakExplanationTolerance);
        $this->closingResistance = self::clampInt($closingResistance);
        $this->boundaryPersistence = self::clampInt($boundaryPersistence);
    }

    public static function forLevel(DifficultyLevel $level): self
    {
        $values = self::DEFAULTS[$level->value] ?? self::DEFAULTS[DifficultyLevel::NORMAL->value];

        return new self(...$values);
    }

    public static function fromCustomConfig(array $config): self
    {
        $defaults = self::DEFAULTS[DifficultyLevel::NORMAL->value];

        return new self(
            trustGainMultiplier: self::extractFloat($config, 'trust_gain_multiplier', $defaults['trustGainMultiplier']),
            trustLossMultiplier: self::extractFloat($config, 'trust_loss_multiplier', $defaults['trustLossMultiplier']),
            disclosureResistance: self::extractInt($config, 'disclosure_resistance', $defaults['disclosureResistance']),
            objectionPersistence: self::extractInt($config, 'objection_persistence', $defaults['objectionPersistence']),
            irritationSensitivity: self::extractInt($config, 'irritation_sensitivity', $defaults['irritationSensitivity']),
            weakExplanationTolerance: self::extractInt($config, 'weak_explanation_tolerance', $defaults['weakExplanationTolerance']),
            closingResistance: self::extractInt($config, 'closing_resistance', $defaults['closingResistance']),
            boundaryPersistence: self::extractInt($config, 'boundary_persistence', $defaults['boundaryPersistence']),
        );
    }

    public function apply(StateTransition $base): StateTransition
    {
        return new StateTransition(
            trustDelta: self::scaleTrust($base->trustDelta, $this->trustGainMultiplier, $this->trustLossMultiplier),
            interestDelta: $base->interestDelta,
            confusionDelta: self::scaleDecrease($base->confusionDelta, $this->weakExplanationTolerance),
            anxietyDelta: $base->anxietyDelta,
            irritationDelta: self::scaleIncrease($base->irritationDelta, $this->irritationSensitivity),
            pressurePerceptionDelta: self::scaleIncrease($base->pressurePerceptionDelta, $this->closingResistance),
            engagementDelta: $base->engagementDelta,
        );
    }

    public function toArray(): array
    {
        return [
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

    public function isDefault(): bool
    {
        return $this->trustGainMultiplier === 1.0
            && $this->trustLossMultiplier === 1.0
            && $this->disclosureResistance === 50
            && $this->objectionPersistence === 50
            && $this->irritationSensitivity === 50
            && $this->weakExplanationTolerance === 50
            && $this->closingResistance === 50
            && $this->boundaryPersistence === 50;
    }

    private static function scaleTrust(int $delta, float $gainMult, float $lossMult): int
    {
        if ($delta > 0) {
            return (int) round($delta * $gainMult);
        }
        if ($delta < 0) {
            return (int) round($delta * $lossMult);
        }
        return 0;
    }

    private static function scaleIncrease(int $delta, int $modifier): int
    {
        if ($delta <= 0) {
            return $delta;
        }
        return (int) round($delta * ($modifier / 50.0));
    }

    private static function scaleDecrease(int $delta, int $modifier): int
    {
        if ($delta <= 0) {
            return $delta;
        }
        return (int) round($delta * ((150 - $modifier) / 100.0));
    }

    private static function clampFloat(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    private static function clampInt(int $value): int
    {
        return max(self::MIN_INT, min(self::MAX_INT, $value));
    }

    private static function extractFloat(array $config, string $key, float $default): float
    {
        $value = $config[$key] ?? $default;
        return is_numeric($value) ? (float) $value : $default;
    }

    private static function extractInt(array $config, string $key, int $default): int
    {
        $value = $config[$key] ?? $default;
        return is_numeric($value) ? (int) $value : $default;
    }
}
