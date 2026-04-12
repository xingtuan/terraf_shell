<?php

namespace App\Enums;

enum UserRole: string
{
    case Visitor = 'visitor';
    case Creator = 'creator';
    case SmePartner = 'sme_partner';
    case Moderator = 'moderator';
    case Admin = 'admin';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role): array => [$role->value => $role->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Visitor => 'Visitor',
            self::Creator => 'Creator',
            self::SmePartner => 'SME Partner',
            self::Moderator => 'Moderator',
            self::Admin => 'Admin',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Visitor => 'gray',
            self::Creator => 'success',
            self::SmePartner => 'info',
            self::Moderator => 'warning',
            self::Admin => 'danger',
        };
    }
}
