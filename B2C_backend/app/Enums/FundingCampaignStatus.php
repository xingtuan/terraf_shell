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
}
