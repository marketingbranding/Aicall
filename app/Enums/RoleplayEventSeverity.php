<?php

namespace App\Enums;

enum RoleplayEventSeverity: string
{
    case LOW = 'LOW';
    case MODERATE = 'MODERATE';
    case HIGH = 'HIGH';
    case CRITICAL = 'CRITICAL';
}
