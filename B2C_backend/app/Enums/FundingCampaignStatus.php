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
            self::Draft => __('admin.funding_campaign_status.draft'),
            self::Scheduled => __('admin.funding_campaign_status.scheduled'),
            self::Live => __('admin.funding_campaign_status.live'),
            self::Paused => __('admin.funding_campaign_status.paused'),
            self::Ended => __('admin.funding_campaign_status.ended'),
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
