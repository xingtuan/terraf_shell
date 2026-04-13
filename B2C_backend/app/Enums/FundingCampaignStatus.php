<?php

namespace App\Enums;

enum FundingCampaignStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Paused = 'paused';
    case Ended = 'ended';

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
            self::Draft => 'Draft',
            self::Scheduled => 'Scheduled',
            self::Live => 'Live',
            self::Paused => 'Paused',
            self::Ended => 'Ended',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Scheduled => 'warning',
            self::Live => 'success',
            self::Paused => 'info',
            self::Ended => 'danger',
        };
    }
}
