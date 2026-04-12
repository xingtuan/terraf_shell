<?php

namespace App\Enums;

enum NotificationType: string
{
    case Comment = 'comment';
    case Reply = 'reply';
    case Like = 'like';
    case Follow = 'follow';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
