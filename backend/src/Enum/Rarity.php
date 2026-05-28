<?php

namespace App\Enum;

enum Rarity: string
{
    case COMMON = 'common';
    case RARE = 'rare';
    case EPIC = 'epic';

    public static function values(): array
    {
        return array_map(static fn (self $rarity): string => $rarity->value, self::cases());
    }
}

