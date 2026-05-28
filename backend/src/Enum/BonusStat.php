<?php

namespace App\Enum;

enum BonusStat: string
{
    case FORCE = 'force';
    case INTELLIGENCE = 'intelligence';
    case DISCIPLINE = 'discipline';
    case CREATIVITY = 'creativity';

    public static function values(): array
    {
        return array_map(static fn (self $stat): string => $stat->value, self::cases());
    }
}

