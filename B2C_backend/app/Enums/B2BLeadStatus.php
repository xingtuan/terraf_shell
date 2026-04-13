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

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::InReview => 'In Review',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'gray',
            self::InReview => 'warning',
            self::Contacted => 'info',
            self::Qualified => 'success',
            self::Closed => 'primary',
        };
    }
}
