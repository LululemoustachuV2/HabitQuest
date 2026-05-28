<?php

namespace App\Enum;

enum NotificationSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case URGENT = 'urgent';

    public static function values(): array
    {
        return array_map(static fn (self $severity): string => $severity->value, self::cases());
    }
}

