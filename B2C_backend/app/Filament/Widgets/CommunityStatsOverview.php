<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\FundingCampaign;
use App\Models\Post;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CommunityStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Community Health';

    protected ?string $description = 'Platform growth and content volume at a glance.';

    protected function getStats(): array
    {
        $totalUsers     = User::query()->count();
        $creators       = User::query()->whereIn('role', [UserRole::Creator->value, 'user'])->count();
        $bannedUsers    = User::query()->where('is_banned', true)->count();
        $totalConcepts  = Post::query()->count();
        $published      = Post::query()->where('status', ContentStatus::Approved->value)->count();
        $featured       = Post::query()->where('is_featured', true)->count();
        $supportEnabled = FundingCampaign::query()->where('support_enabled', true)->count();

        return [
            Stat::make('Total users', number_format($totalUsers))
                ->description(number_format($creators) . ' creators')
                ->color('primary')
                ->icon('heroicon-o-users')
                ->url(UserResource::getUrl()),
            Stat::make('Published concepts', number_format($published))
                ->description(number_format($totalConcepts) . ' total concepts')
                ->color('success')
                ->icon('heroicon-o-light-bulb')
                ->url(PostResource::getUrl()),
            Stat::make('Featured concepts', number_format($featured))
                ->description('Editor-promoted on the platform')
                ->color('info')
                ->icon('heroicon-o-star')
                ->url(PostResource::getUrl()),
            Stat::make('Support-enabled', number_format($supportEnabled))
                ->description('Concepts with an active funding CTA')
                ->color('warning')
                ->icon('heroicon-o-heart')
                ->url(PostResource::getUrl()),
            Stat::make('Banned users', number_format($bannedUsers))
                ->description('Accounts currently blocked')
                ->color($bannedUsers > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-no-symbol')
                ->url(UserResource::getUrl()),
        ];
    }
}
