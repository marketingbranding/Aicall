<?php

namespace App\Enums;

enum RoleplaySessionStatus: string
{
    case CREATED = 'CREATED';
    case PREPARING = 'PREPARING';
    case REQUESTING_MICROPHONE = 'REQUESTING_MICROPHONE';
    case CONNECTING = 'CONNECTING';
    case READY = 'READY';
    case ACTIVE = 'ACTIVE';
    case RECONNECTING = 'RECONNECTING';
    case ENDING = 'ENDING';
    case TRANSCRIPT_FINALIZING = 'TRANSCRIPT_FINALIZING';
    case EVALUATING = 'EVALUATING';
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::COMPLETED, self::FAILED => true,
            default => false,
        };
    }

    public function isActive(): bool
    {
        return match ($this) {
            self::ACTIVE, self::READY => true,
            default => false,
        };
    }

    public function canReceiveEvents(): bool
    {
        return match ($this) {
            self::ACTIVE, self::READY => true,
            default => false,
        };
    }
}
