<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'SUPER_ADMIN';
    case Sales = 'SALES';

    public function canAccessHq(): bool
    {
        return match ($this) {
            self::SuperAdmin => true,
            self::Sales => false,
        };
    }

    public function canManageBranches(): bool
    {
        return match ($this) {
            self::SuperAdmin => true,
            self::Sales => false,
        };
    }

    public function canManageUsers(): bool
    {
        return match ($this) {
            self::SuperAdmin => true,
            self::Sales => false,
        };
    }

    public function canApproveUsers(): bool
    {
        return match ($this) {
            self::SuperAdmin => true,
            self::Sales => false,
        };
    }

    public function canManagePersonas(): bool
    {
        return match ($this) {
            self::SuperAdmin => true,
            self::Sales => false,
        };
    }

    public function canManageScenarios(): bool
    {
        return match ($this) {
            self::SuperAdmin => true,
            self::Sales => false,
        };
    }

    public function canManageRubrics(): bool
    {
        return match ($this) {
            self::SuperAdmin => true,
            self::Sales => false,
        };
    }

    public function canConfigureAiProviders(): bool
    {
        return match ($this) {
            self::SuperAdmin => true,
            self::Sales => false,
        };
    }

    public function canViewAllTrainingSessions(): bool
    {
        return match ($this) {
            self::SuperAdmin => true,
            self::Sales => false,
        };
    }
}
