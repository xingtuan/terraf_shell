<?php

namespace App\Filament\Resources\FundingCampaigns\Pages;

use App\Filament\Resources\FundingCampaigns\FundingCampaignResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFundingCampaign extends ViewRecord
{
    protected static string $resource = FundingCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
