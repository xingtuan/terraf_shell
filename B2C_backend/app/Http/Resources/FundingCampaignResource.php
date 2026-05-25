<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocalizedFields;
use App\Models\FundingCampaign;
use App\Services\CommunitySettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FundingCampaign */
class FundingCampaignResource extends JsonResource
{
    use ResolvesLocalizedFields;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $defaultSupportButtonText = app(CommunitySettingsService::class)->defaultFundingSupportButtonText();
        $supportButtonText = trim((string) $this->localizedString($request, 'support_button_text'));

        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'support_enabled' => (bool) $this->support_enabled,
            'support_button_text' => $supportButtonText !== '' ? $supportButtonText : $defaultSupportButtonText,
            'support_button_text_translations' => $this->localizedStringSet('support_button_text'),
            'external_crowdfunding_url' => $this->external_crowdfunding_url,
            'campaign_status' => $this->campaign_status,
            'target_amount' => $this->target_amount !== null ? (float) $this->target_amount : null,
            'pledged_amount' => $this->pledged_amount !== null ? (float) $this->pledged_amount : null,
            'backer_count' => $this->backer_count !== null ? (int) $this->backer_count : null,
            'reward_description' => $this->localizedString($request, 'reward_description'),
            'reward_description_translations' => $this->localizedStringSet('reward_description'),
            'campaign_start_at' => $this->campaign_start_at?->toISOString(),
            'campaign_end_at' => $this->campaign_end_at?->toISOString(),
            'progress_percentage' => $this->progressPercentage(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
