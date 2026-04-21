<?php

namespace App\Http\Resources;

use App\Models\FundingCampaign;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Post */
class PostResource extends JsonResource
{
    private bool $includeDetailFields = false;

    public function includeDetailFields(): self
    {
        $this->includeDetailFields = true;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $campaign = $this->visibleFundingCampaign($request);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'content_json' => $this->when($this->includeDetailFields, $this->content_json),
            'excerpt' => $this->excerpt,
            'funding_url' => $this->funding_url,
            'cover_image_url' => $this->coverImageUrl(),
            'cover_image_path' => $this->when($this->includeDetailFields, $this->cover_image_path),
            'reading_time' => (int) ($this->reading_time ?? 0),
            'status' => $this->status,
            'is_pinned' => (bool) $this->is_pinned,
            'is_featured' => (bool) $this->is_featured,
            'engagement_score' => (int) ($this->engagement_score ?? 0),
            'trending_score' => (int) ($this->trending_score ?? 0),
            'views_count' => (int) ($this->views_count ?? 0),
            'support_enabled' => (bool) ($campaign?->support_enabled ?? false),
            'support_button_text' => $campaign?->support_button_text,
            'external_crowdfunding_url' => $campaign?->external_crowdfunding_url,
            'campaign_status' => $campaign?->campaign_status,
            'target_amount' => $campaign?->target_amount !== null ? (float) $campaign->target_amount : null,
            'pledged_amount' => $campaign?->pledged_amount !== null ? (float) $campaign->pledged_amount : null,
            'backer_count' => $campaign?->backer_count !== null ? (int) $campaign->backer_count : null,
            'reward_description' => $campaign?->reward_description,
            'campaign_start_at' => $campaign?->campaign_start_at?->toISOString(),
            'campaign_end_at' => $campaign?->campaign_end_at?->toISOString(),
            'funding_campaign' => $this->when(
                $campaign !== null,
                fn (): FundingCampaignResource => new FundingCampaignResource($campaign)
            ),
            'comments_count' => (int) $this->comments_count,
            'likes_count' => (int) $this->likes_count,
            'favorites_count' => (int) $this->favorites_count,
            'is_liked' => (bool) ($this->is_liked ?? false),
            'is_favorited' => (bool) ($this->is_favorited ?? false),
            'user' => new UserResource($this->whenLoaded('user')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'images' => PostImageResource::collection($this->whenLoaded('images')),
            'media' => IdeaMediaResource::collection($this->whenLoaded('media')),
            'featured_by' => $this->when(
                $request->user()?->canModerate() ?? false,
                $this->featured_by
            ),
            'can_edit' => $request->user()?->can('update', $this->resource) ?? false,
            'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
            'featured_at' => $this->featured_at?->toISOString(),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function visibleFundingCampaign(Request $request): ?FundingCampaign
    {
        if (! $this->relationLoaded('fundingCampaign') || ! ($this->fundingCampaign instanceof FundingCampaign)) {
            return null;
        }

        return $this->fundingCampaign->isVisibleTo($request->user())
            ? $this->fundingCampaign
            : null;
    }
}
