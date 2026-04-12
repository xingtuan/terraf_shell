<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Enums\ReportStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CommunityStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Community Overview';

    protected ?string $description = 'Operational metrics for moderators and administrators.';

    protected function getStats(): array
    {
        return [
            Stat::make('Total users', number_format(User::query()->count()))
                ->description('All registered community members')
                ->color('primary'),
            Stat::make('Total posts', number_format(Post::query()->count()))
                ->description('Published and moderated posts combined')
                ->color('primary'),
            Stat::make('Total comments', number_format(Comment::query()->count()))
                ->description('Top-level comments and replies')
                ->color('primary'),
            Stat::make('Pending posts', number_format(Post::query()->where('status', ContentStatus::Pending->value)->count()))
                ->description('Posts waiting for review')
                ->color('warning'),
            Stat::make('Pending comments', number_format(Comment::query()->where('status', ContentStatus::Pending->value)->count()))
                ->description('Comments waiting for review')
                ->color('warning'),
            Stat::make('Open reports', number_format(Report::query()->where('status', ReportStatus::Pending->value)->count()))
                ->description('Reports that still need action')
                ->color('danger'),
            Stat::make('Banned users', number_format(User::query()->where('is_banned', true)->count()))
                ->description('Accounts currently blocked')
                ->color('gray'),
        ];
    }
}
