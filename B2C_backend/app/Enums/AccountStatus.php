<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case Restricted = 'restricted';
    case Banned = 'banned';

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
            self::Active => 'Active',
            self::Restricted => 'Restricted',
            self::Banned => 'Banned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Restricted => 'warning',
            self::Banned => 'danger',
        };
    }
}
