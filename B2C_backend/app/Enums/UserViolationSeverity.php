<?php

namespace App\Enums;

enum UserViolationSeverity: string
{
    case Notice = 'notice';
    case Warning = 'warning';
    case Restriction = 'restriction';
    case Ban = 'ban';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
