<?php

namespace App\Enums;

enum B2BLeadStatus: string
{
    case New = 'new';
    case InReview = 'in_review';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Closed = 'closed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
