<?php

namespace App\Enum;

enum StatType: string
{
    case FORCE = 'force';
    case INTELLIGENCE = 'intelligence';
    case DISCIPLINE = 'discipline';
    case CREATIVITY = 'creativity';

    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}

