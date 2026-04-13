<?php

namespace App\Services;

use App\Models\FundingCampaign;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FundingCampaignService
{
    public function __construct(
        private readonly GovernanceService $governanceService,
    ) {}

    public function showForAdmin(Post $post): ?FundingCampaign
    {
        return $post->load('fundingCampaign')->fundingCampaign;
    }

    public function upsertForPost(Post $post, array $data, User $admin): FundingCampaign
    {
        return DB::transaction(function () use ($post, $data, $admin): FundingCampaign {
            $post->loadMissing('user', 'fundingCampaign');
            $campaign = $post->fundingCampaign ?: new FundingCampaign(['post_id' => $post->id]);
            $isNew = ! $campaign->exists;
            $before = $campaign->exists ? $campaign->only($this->trackedFields()) : [];

            $campaign->fill([
                'support_enabled' => (bool) $data['support_enabled'],
                'support_button_text' => trim((string) ($data['support_button_text'] ?? '')) ?: $this->defaultButtonText(),
                'external_crowdfunding_url' => $data['external_crowdfunding_url'] ?? null,
                'campaign_status' => $data['campaign_status'],
                'target_amount' => $data['target_amount'] ?? null,
                'pledged_amount' => $data['pledged_amount'] ?? null,
                'backer_count' => $data['backer_count'] ?? null,
                'reward_description' => $data['reward_description'] ?? null,
                'campaign_start_at' => $data['campaign_start_at'] ?? null,
                'campaign_end_at' => $data['campaign_end_at'] ?? null,
            ]);
            $campaign->post()->associate($post);
            $campaign->save();

            $this->recordAdminAction(
                $admin,
                $post,
                $isNew,
                $this->changes($before, $campaign->fresh()->only($this->trackedFields())),
                $post->user
            );

            return $campaign->fresh();
        });
    }

    public function deleteForPost(Post $post, User $admin): void
    {
        DB::transaction(function () use ($post, $admin): void {
            $post->loadMissing('user', 'fundingCampaign');
            $campaign = $post->fundingCampaign;

            if ($campaign === null) {
                return;
            }

            $metadata = [
                'campaign_id' => $campaign->id,
                'campaign_status' => $campaign->campaign_status,
                'support_enabled' => (bool) $campaign->support_enabled,
            ];

            $campaign->delete();

            $this->governanceService->recordAdminAction(
                $admin,
                'post.funding_campaign_deleted',
                'Funding campaign removed from concept.',
                $metadata,
                $post,
                $post->user
            );
        });
    }

    private function defaultButtonText(): string
    {
        return (string) config('community.funding.default_support_button_text', 'Support this concept');
    }

    /**
     * @return array<int, string>
     */
    private function trackedFields(): array
    {
        return [
            'support_enabled',
            'support_button_text',
            'external_crowdfunding_url',
            'campaign_status',
            'target_amount',
            'pledged_amount',
            'backer_count',
            'reward_description',
            'campaign_start_at',
            'campaign_end_at',
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array<string, mixed>>
     */
    private function changes(array $before, array $after): array
    {
        $changes = [];

        foreach ($after as $key => $value) {
            $from = $before[$key] ?? null;
            $to = $value;

            if ($from === $to) {
                continue;
            }

            $changes[$key] = [
                'from' => $this->normalizeValue($from),
                'to' => $this->normalizeValue($to),
            ];
        }

        return $changes;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        return $value;
    }

    private function recordAdminAction(
        User $admin,
        Post $post,
        bool $isNew,
        array $changes,
        ?User $targetUser = null,
    ): void {
        $this->governanceService->recordAdminAction(
            $admin,
            $isNew ? 'post.funding_campaign_created' : 'post.funding_campaign_updated',
            $isNew ? 'Funding campaign attached to concept.' : 'Funding campaign updated for concept.',
            $changes,
            $post,
            $targetUser
        );
    }
}
