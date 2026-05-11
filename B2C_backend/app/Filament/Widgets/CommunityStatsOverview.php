<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\FundingCampaign;
use App\Models\IdeaMedia;
use App\Models\Post;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CommunityStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = null;

    protected ?string $description = null;

    protected function getHeading(): ?string
    {
        return __('admin.widgets.community_health');
    }

    protected function getDescription(): ?string
    {
        return __('admin.ui.community_stats_description');
    }

    protected function getStats(): array
    {
        $totalUsers = User::query()->count();
        $creators = User::query()->whereIn('role', [UserRole::Creator->value, 'user'])->count();
        $bannedUsers = User::query()->where('is_banned', true)->count();
        $totalConcepts = Post::query()->count();
        $published = Post::query()->where('status', ContentStatus::Approved->value)->count();
        $featured = Post::query()->where('is_featured', true)->count();
        $supportEnabled = FundingCampaign::query()->where('support_enabled', true)->count();
        $demoContent = Post::query()->where('is_demo_content', true)->count();
        $recentUploads = IdeaMedia::query()->where('created_at', '>=', now()->subDays(7))->count();

        return [
            Stat::make(__('admin.ui.total_users'), number_format($totalUsers))
                ->description(__('admin.ui.n_creators', ['count' => number_format($creators)]))
                ->color('primary')
                ->icon('heroicon-o-users')
                ->url(UserResource::getUrl()),
            Stat::make(__('admin.ui.published_concepts'), number_format($published))
                ->description(__('admin.ui.n_total_concepts', ['count' => number_format($totalConcepts)]))
                ->color('success')
                ->icon('heroicon-o-light-bulb')
                ->url(PostResource::getUrl()),
            Stat::make(__('admin.ui.featured_concepts'), number_format($featured))
                ->description(__('admin.ui.editor_promoted_on_the_platform'))
                ->color('info')
                ->icon('heroicon-o-star')
                ->url(PostResource::getUrl()),
            Stat::make(__('admin.ui.support_enabled'), number_format($supportEnabled))
                ->description(__('admin.ui.concepts_with_an_active_funding_cta'))
                ->color('warning')
                ->icon('heroicon-o-heart')
                ->url(PostResource::getUrl()),
            Stat::make(__('admin.ui.recent_uploads'), number_format($recentUploads))
                ->description(__('admin.ui.community_media_uploaded_in_the_last_7_days'))
                ->color('info')
                ->icon('heroicon-o-photo')
                ->url(PostResource::getUrl()),
            Stat::make(__('admin.ui.demo_content'), number_format($demoContent))
                ->description(__('admin.ui.seeded_records_to_clean_before_launch'))
                ->color($demoContent > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-trash')
                ->url(PostResource::getUrl()),
            Stat::make(__('admin.ui.banned_users'), number_format($bannedUsers))
                ->description(__('admin.ui.accounts_currently_blocked'))
                ->color($bannedUsers > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-no-symbol')
                ->url(UserResource::getUrl()),
        ];
    }
}
