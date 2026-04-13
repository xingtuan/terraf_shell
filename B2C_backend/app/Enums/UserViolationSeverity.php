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

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $severity): array => [$severity->value => $severity->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Notice => 'Notice',
            self::Warning => 'Warning',
            self::Restriction => 'Restriction',
            self::Ban => 'Ban',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Notice => 'gray',
            self::Warning => 'warning',
            self::Restriction => 'danger',
            self::Ban => 'danger',
        };
    }
}
