<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'SUPER_ADMIN';
    case Sales = 'SALES';

    /**
     * Centralize HQ access so future roles can be added without scattered checks.
     */
    public function canAccessHq(): bool
    {
        return match ($this) {
            self::SuperAdmin => true,
            self::Sales => false,
        };
    }
}
