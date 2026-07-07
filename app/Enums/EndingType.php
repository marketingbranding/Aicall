<?php

namespace App\Enums;

enum EndingType: string
{
    case USER_END = 'USER_END';
    case AI_END = 'AI_END';
    case TIME_LIMIT = 'TIME_LIMIT';
    case FAILURE = 'FAILURE';
}
