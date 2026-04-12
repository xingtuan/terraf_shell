<?php

namespace App\Enums;

enum IdeaMediaSourceType: string
{
    case Upload = 'upload';
    case ExternalUrl = 'external_url';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
