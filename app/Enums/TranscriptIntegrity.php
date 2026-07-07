<?php

namespace App\Enums;

enum TranscriptIntegrity: string
{
    case COMPLETE = 'COMPLETE';
    case PARTIAL = 'PARTIAL';
    case FAILED = 'FAILED';
}
