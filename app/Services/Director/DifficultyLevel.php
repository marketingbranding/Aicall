<?php

namespace App\Services\Director;

enum DifficultyLevel: string
{
    case BEGINNER = 'BEGINNER';
    case NORMAL = 'NORMAL';
    case DIFFICULT = 'DIFFICULT';
    case EXPERT = 'EXPERT';
    case CUSTOM = 'CUSTOM';
}
