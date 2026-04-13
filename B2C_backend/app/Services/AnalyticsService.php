<?php

namespace App\Services;

use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use App\Enums\ContentStatus;
use App\Enums\FundingCampaignStatus;
use App\Enums\UserRole;
use App\Models\B2BLead;
use App\Models\FundingCampaign;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnalyticsService
{
    public function overview(int $limit = 5): array
    {
        return [
            'generated_at' => now()->toISOString(),
            'summary' => $this->summary(),
            'categories' => [
                'most_common' => $this->categoryMetrics('volume', $limit),
                'highest_engagement' => $this->categoryMetrics('engagement', $limit),
            ],
            'activity' => [
                'schools_or_companies' => $this->schoolCompanyActivity($limit),
                'user_groups' => $this->userGroupActivity(),
            ],
            'attention' => [
                'tracking' => [
                    'concept_views_available' => true,
                    'page_views_available' => false,
                    'cta_sources_available' => true,
                    'concept_view_source' => 'posts.views_count',
                    'cta_source_field' => 'inquiries.source_page',
                ],
                'most_viewed_concepts' => $this->mostViewedConcepts($limit),
                'best_performing_cta_sources' => $this->bestPerformingCtaSources($limit),
            ],
            'funding' => [
                'readiness_formula' => $this->fundingReadinessFormula(),
                'most_likely_concepts' => $this->fundingReadyConcepts($limit),
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function summary(): array
    {
        return [
            'total_concepts' => (int) Post::query()->count(),
            'approved_concepts' => (int) Post::query()->where('status', ContentStatus::Approved->value)->count(),
            'pending_concepts' => (int) Post::query()->where('status', ContentStatus::Pending->value)->count(),
            'total_views' => (int) Post::query()->sum('views_count'),
            'total_engagement' => (int) Post::query()->sum('engagement_score'),
            'concepts_with_funding_campaigns' => (int) FundingCampaign::query()->count(),
            'support_enabled_concepts' => (int) FundingCampaign::query()->where('support_enabled', true)->count(),
            'total_b2b_leads' => (int) B2BLead::query()->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function categoryMetrics(string $mode, int $limit): array
    {
        $rows = DB::table('posts')
            ->join('categories', 'categories.id', '=', 'posts.category_id')
            ->select([
                'categories.id as category_id',
                'categories.name',
                'categories.slug',
            ])
            ->selectRaw('COUNT(posts.id) as total_concepts')
            ->selectRaw(
                'SUM(CASE WHEN posts.status = ? THEN 1 ELSE 0 END) as approved_concepts',
                [ContentStatus::Approved->value]
            )
            ->selectRaw('COALESCE(SUM(posts.engagement_score), 0) as total_engagement')
            ->selectRaw('COALESCE(AVG(posts.engagement_score), 0) as average_engagement')
            ->selectRaw('COALESCE(SUM(posts.views_count), 0) as total_views')
            ->groupBy('categories.id', 'categories.name', 'categories.slug');

        if ($mode === 'engagement') {
            $rows->orderByDesc('total_engagement')
                ->orderByDesc('total_views')
                ->orderByDesc('total_concepts');
        } else {
            $rows->orderByDesc('total_concepts')
                ->orderByDesc('total_engagement')
                ->orderByDesc('total_views');
        }

        return $rows
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => [
                'category_id' => (int) $row->category_id,
                'name' => $row->name,
                'slug' => $row->slug,
                'total_concepts' => (int) $row->total_concepts,
                'approved_concepts' => (int) $row->approved_concepts,
                'total_engagement' => (int) $row->total_engagement,
                'average_engagement' => round((float) $row->average_engagement, 2),
                'total_views' => (int) $row->total_views,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function schoolCompanyActivity(int $limit): array
    {
        $userStats = DB::table('profiles')
            ->join('users', 'users.id', '=', 'profiles.user_id')
            ->whereNotNull('profiles.school_or_company')
            ->where('profiles.school_or_company', '!=', '')
            ->select('profiles.school_or_company')
            ->selectRaw('COUNT(users.id) as total_users')
            ->selectRaw('SUM(CASE WHEN profiles.open_to_collab = 1 THEN 1 ELSE 0 END) as open_to_collab_users')
            ->groupBy('profiles.school_or_company')
            ->get()
            ->keyBy('school_or_company');

        $postStats = DB::table('posts')
            ->join('users', 'users.id', '=', 'posts.user_id')
            ->join('profiles', 'profiles.user_id', '=', 'users.id')
            ->whereNotNull('profiles.school_or_company')
            ->where('profiles.school_or_company', '!=', '')
            ->select('profiles.school_or_company')
            ->selectRaw('COUNT(posts.id) as total_concepts')
            ->selectRaw(
                'SUM(CASE WHEN posts.status = ? THEN 1 ELSE 0 END) as approved_concepts',
                [ContentStatus::Approved->value]
            )
            ->selectRaw('COALESCE(SUM(posts.engagement_score), 0) as total_engagement')
            ->selectRaw('COALESCE(SUM(posts.views_count), 0) as total_views')
            ->groupBy('profiles.school_or_company')
            ->get()
            ->keyBy('school_or_company');

        return $postStats
            ->map(function (object $postRow, string $schoolOrCompany) use ($userStats): array {
                $userRow = $userStats->get($schoolOrCompany);

                return [
                    'school_or_company' => $schoolOrCompany,
                    'total_users' => (int) ($userRow->total_users ?? 0),
                    'open_to_collab_users' => (int) ($userRow->open_to_collab_users ?? 0),
                    'total_concepts' => (int) $postRow->total_concepts,
                    'approved_concepts' => (int) $postRow->approved_concepts,
                    'total_engagement' => (int) $postRow->total_engagement,
                    'total_views' => (int) $postRow->total_views,
                ];
            })
            ->sortByDesc(fn (array $row): array => [
                $row['total_concepts'],
                $row['total_engagement'],
                $row['total_views'],
            ])
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function userGroupActivity(): array
    {
        $userCounts = DB::table('users')
            ->select('role')
            ->selectRaw('COUNT(*) as total_users')
            ->groupBy('role')
            ->get();

        $postCounts = DB::table('posts')
            ->join('users', 'users.id', '=', 'posts.user_id')
            ->select('users.role')
            ->selectRaw('COUNT(posts.id) as total_concepts')
            ->selectRaw(
                'SUM(CASE WHEN posts.status = ? THEN 1 ELSE 0 END) as approved_concepts',
                [ContentStatus::Approved->value]
            )
            ->selectRaw('COALESCE(SUM(posts.engagement_score), 0) as total_engagement')
            ->selectRaw('COALESCE(SUM(posts.views_count), 0) as total_views')
            ->groupBy('users.role')
            ->get();

        $aggregated = [];

        foreach ($userCounts as $row) {
            $role = $this->normalizeRoleValue((string) $row->role);
            $aggregated[$role] ??= $this->emptyUserGroupRow($role);
            $aggregated[$role]['total_users'] += (int) $row->total_users;
        }

        foreach ($postCounts as $row) {
            $role = $this->normalizeRoleValue((string) $row->role);
            $aggregated[$role] ??= $this->emptyUserGroupRow($role);
            $aggregated[$role]['total_concepts'] += (int) $row->total_concepts;
            $aggregated[$role]['approved_concepts'] += (int) $row->approved_concepts;
            $aggregated[$role]['total_engagement'] += (int) $row->total_engagement;
            $aggregated[$role]['total_views'] += (int) $row->total_views;
        }

        return collect($aggregated)
            ->sortByDesc(fn (array $row): array => [
                $row['total_concepts'],
                $row['total_users'],
                $row['total_engagement'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mostViewedConcepts(int $limit): array
    {
        return Post::query()
            ->approved()
            ->with(['user.profile', 'category', 'fundingCampaign'])
            ->orderByDesc('views_count')
            ->orderByDesc('engagement_score')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Post $post): array => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'views_count' => (int) $post->views_count,
                'engagement_score' => (int) $post->engagement_score,
                'category' => $post->category?->name,
                'creator_name' => $post->user?->name,
                'creator_username' => $post->user?->username,
                'school_or_company' => $post->user?->profile?->school_or_company,
                'campaign_status' => $post->fundingCampaign?->campaign_status,
                'support_enabled' => (bool) ($post->fundingCampaign?->support_enabled ?? false),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function bestPerformingCtaSources(int $limit): array
    {
        $qualifiedStatuses = [
            B2BLeadStatus::Qualified->value,
            B2BLeadStatus::Closed->value,
        ];
        $collaborationLeadTypes = [
            B2BLeadType::PartnershipInquiry->value,
            B2BLeadType::UniversityCollaboration->value,
            B2BLeadType::ProductDevelopmentCollaboration->value,
        ];

        $rows = DB::table('inquiries')
            ->whereNotNull('source_page')
            ->where('source_page', '!=', '')
            ->select('source_page')
            ->selectRaw('COUNT(*) as lead_count')
            ->selectRaw(
                'SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as high_intent_lead_count',
                $qualifiedStatuses
            )
            ->selectRaw(
                'SUM(CASE WHEN lead_type = ? THEN 1 ELSE 0 END) as sample_request_count',
                [B2BLeadType::SampleRequest->value]
            )
            ->selectRaw(
                'SUM(CASE WHEN lead_type IN (?, ?, ?) THEN 1 ELSE 0 END) as collaboration_lead_count',
                $collaborationLeadTypes
            )
            ->groupBy('source_page')
            ->orderByDesc('high_intent_lead_count')
            ->orderByDesc('lead_count')
            ->limit($limit)
            ->get();

        return $rows->map(function (object $row): array {
            $leadCount = (int) $row->lead_count;
            $highIntent = (int) $row->high_intent_lead_count;

            return [
                'source_page' => $row->source_page,
                'lead_count' => $leadCount,
                'high_intent_lead_count' => $highIntent,
                'sample_request_count' => (int) $row->sample_request_count,
                'collaboration_lead_count' => (int) $row->collaboration_lead_count,
                'high_intent_rate' => $leadCount > 0
                    ? round(($highIntent / $leadCount) * 100, 2)
                    : 0.0,
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function fundingReadinessFormula(): array
    {
        return [
            'formula' => '(engagement_score) + (favorites_count * 3) + (views_count / 10) + featured_bonus + collab_bonus + support_bonus',
            'featured_bonus' => 20,
            'collab_bonus' => 10,
            'support_bonus' => 15,
            'eligible_campaign_statuses' => [
                FundingCampaignStatus::Draft->value,
                FundingCampaignStatus::Scheduled->value,
                FundingCampaignStatus::Paused->value,
                null,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fundingReadyConcepts(int $limit): array
    {
        $eligibleCampaignStatuses = [
            FundingCampaignStatus::Draft->value,
            FundingCampaignStatus::Scheduled->value,
            FundingCampaignStatus::Paused->value,
        ];

        $rows = DB::table('posts')
            ->join('users', 'users.id', '=', 'posts.user_id')
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('categories', 'categories.id', '=', 'posts.category_id')
            ->leftJoin('funding_campaigns', 'funding_campaigns.post_id', '=', 'posts.id')
            ->where('posts.status', ContentStatus::Approved->value)
            ->where(function ($query) use ($eligibleCampaignStatuses): void {
                $query
                    ->whereNull('funding_campaigns.campaign_status')
                    ->orWhereIn('funding_campaigns.campaign_status', $eligibleCampaignStatuses);
            })
            ->select([
                'posts.id',
                'posts.title',
                'posts.slug',
                'posts.likes_count',
                'posts.comments_count',
                'posts.favorites_count',
                'posts.engagement_score',
                'posts.views_count',
                'posts.is_featured',
                'users.name as creator_name',
                'users.username as creator_username',
                'categories.name as category_name',
                'profiles.school_or_company',
                'profiles.open_to_collab',
                'funding_campaigns.support_enabled',
                'funding_campaigns.campaign_status',
            ])
            ->selectRaw(
                '(
                    posts.engagement_score
                    + (posts.favorites_count * 3)
                    + (posts.views_count / 10.0)
                    + (CASE WHEN posts.is_featured = 1 THEN 20 ELSE 0 END)
                    + (CASE WHEN profiles.open_to_collab = 1 THEN 10 ELSE 0 END)
                    + (CASE WHEN funding_campaigns.support_enabled = 1 THEN 15 ELSE 0 END)
                ) as funding_readiness_score'
            )
            ->orderByDesc('funding_readiness_score')
            ->orderByDesc('posts.engagement_score')
            ->orderByDesc('posts.views_count')
            ->orderByDesc('posts.created_at')
            ->limit($limit)
            ->get();

        return $rows->map(fn (object $row): array => [
            'id' => (int) $row->id,
            'title' => $row->title,
            'slug' => $row->slug,
            'category' => $row->category_name,
            'creator_name' => $row->creator_name,
            'creator_username' => $row->creator_username,
            'school_or_company' => $row->school_or_company,
            'likes_count' => (int) $row->likes_count,
            'comments_count' => (int) $row->comments_count,
            'favorites_count' => (int) $row->favorites_count,
            'engagement_score' => (int) $row->engagement_score,
            'views_count' => (int) $row->views_count,
            'is_featured' => (bool) $row->is_featured,
            'open_to_collab' => (bool) $row->open_to_collab,
            'support_enabled' => (bool) $row->support_enabled,
            'campaign_status' => $row->campaign_status,
            'funding_readiness_score' => round((float) $row->funding_readiness_score, 2),
            'recommended_next_action' => $this->recommendedFundingAction(
                $row->campaign_status,
                (bool) $row->support_enabled
            ),
        ])->all();
    }

    /**
     * @return array<string, int|string>
     */
    private function emptyUserGroupRow(string $role): array
    {
        return [
            'role' => $role,
            'label' => $this->roleLabel($role),
            'total_users' => 0,
            'total_concepts' => 0,
            'approved_concepts' => 0,
            'total_engagement' => 0,
            'total_views' => 0,
        ];
    }

    private function normalizeRoleValue(string $role): string
    {
        return $role === 'user'
            ? UserRole::Creator->value
            : $role;
    }

    private function roleLabel(string $role): string
    {
        return UserRole::tryFrom($role)?->label() ?? Str::headline($role);
    }

    private function recommendedFundingAction(?string $campaignStatus, bool $supportEnabled): string
    {
        if ($campaignStatus === null) {
            return 'Attach an external crowdfunding link and enable the support CTA.';
        }

        if (! $supportEnabled) {
            return 'Enable the support CTA before promoting this concept externally.';
        }

        return match ($campaignStatus) {
            FundingCampaignStatus::Draft->value => 'Publish the campaign link when the launch window is confirmed.',
            FundingCampaignStatus::Scheduled->value => 'Prepare launch communications and move the campaign live.',
            FundingCampaignStatus::Paused->value => 'Refresh the campaign link and resume promotion when ready.',
            default => 'Review campaign progress and next-stage funding signals.',
        };
    }
}
