<?php

namespace App\Filament\Resources\FundingCampaigns\Pages;

use App\Filament\Resources\FundingCampaigns\FundingCampaignResource;
use App\Filament\Support\PanelAccess;
use App\Models\Post;
use App\Services\FundingCampaignService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateFundingCampaign extends CreateRecord
{
    protected static string $resource = FundingCampaignResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $post = Post::query()->findOrFail((int) $data['post_id']);

        return app(FundingCampaignService::class)->upsertForPost(
            $post,
            $data,
            PanelAccess::user(),
        );
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Funding campaign attached';
    }
}
