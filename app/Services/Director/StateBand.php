<?php

namespace App\Services\Director;

enum StateBand: string
{
    case VERY_LOW = 'very_low';
    case LOW = 'low';
    case MODERATE = 'moderate';
    case HIGH = 'high';
    case VERY_HIGH = 'very_high';

    public static function fromValue(int $value): self
    {
        return match (true) {
            $value <= 20 => self::VERY_LOW,
            $value <= 40 => self::LOW,
            $value <= 60 => self::MODERATE,
            $value <= 80 => self::HIGH,
            default => self::VERY_HIGH,
        };
    }
}
