<?php

namespace App\Enum;

enum AffinityStat: string
{
    case FORCE = 'force';
    case INTELLIGENCE = 'intelligence';
    case DISCIPLINE = 'discipline';
    case CREATIVITY = 'creativity';
    case NEUTRAL = 'neutral';

    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}

