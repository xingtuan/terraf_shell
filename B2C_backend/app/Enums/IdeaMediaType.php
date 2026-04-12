<?php

namespace App\Enums;

enum IdeaMediaType: string
{
    case Image = 'image';
    case Document = 'document';
    case External3d = 'external_3d';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
