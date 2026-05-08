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

    protected ?string $description = 'Platform growth and content volume at a glance.';

    protected function getHeading(): ?string
    {
        return __('admin.widgets.community_health');
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
            Stat::make('Total users', number_format($totalUsers))
                ->description(number_format($creators).' creators')
                ->color('primary')
                ->icon('heroicon-o-users')
                ->url(UserResource::getUrl()),
            Stat::make('Published concepts', number_format($published))
                ->description(number_format($totalConcepts).' total concepts')
                ->color('success')
                ->icon('heroicon-o-light-bulb')
                ->url(PostResource::getUrl()),
            Stat::make('Featured concepts', number_format($featured))
                ->description(__('admin.ui.editor_promoted_on_the_platform'))
                ->color('info')
                ->icon('heroicon-o-star')
                ->url(PostResource::getUrl()),
            Stat::make('Support-enabled', number_format($supportEnabled))
                ->description(__('admin.ui.concepts_with_an_active_funding_cta'))
                ->color('warning')
                ->icon('heroicon-o-heart')
                ->url(PostResource::getUrl()),
            Stat::make('Recent uploads', number_format($recentUploads))
                ->description(__('admin.ui.community_media_uploaded_in_the_last_7_days'))
                ->color('info')
                ->icon('heroicon-o-photo')
                ->url(PostResource::getUrl()),
            Stat::make('Demo content', number_format($demoContent))
                ->description(__('admin.ui.seeded_records_to_clean_before_launch'))
                ->color($demoContent > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-trash')
                ->url(PostResource::getUrl()),
            Stat::make('Banned users', number_format($bannedUsers))
                ->description(__('admin.ui.accounts_currently_blocked'))
                ->color($bannedUsers > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-no-symbol')
                ->url(UserResource::getUrl()),
        ];
    }
}
