<?php

namespace App\Filament\Resources\FundingCampaigns\Pages;

use App\Filament\Resources\FundingCampaigns\FundingCampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFundingCampaigns extends ListRecords
{
    protected static string $resource = FundingCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
