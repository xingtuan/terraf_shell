<?php

namespace App\Filament\Widgets;

use App\Enums\B2BLeadStatus;
use App\Enums\ContentStatus;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\B2BLead;
use App\Models\Comment;
use App\Models\FundingCampaign;
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
            Stat::make('Creators', number_format(User::query()->whereIn('role', [UserRole::Creator->value, 'user'])->count()))
                ->description('Accounts that can submit concepts')
                ->color('success'),
            Stat::make('Total concepts', number_format(Post::query()->count()))
                ->description('Published and moderated concepts combined')
                ->color('primary'),
            Stat::make('Pending posts', number_format(Post::query()->where('status', ContentStatus::Pending->value)->count()))
                ->description('Posts waiting for review')
                ->color('warning'),
            Stat::make('Published concepts', number_format(Post::query()->where('status', ContentStatus::Approved->value)->count()))
                ->description('Concepts visible on the public platform')
                ->color('success'),
            Stat::make('Featured concepts', number_format(Post::query()->where('is_featured', true)->count()))
                ->description('Editor-promoted concepts')
                ->color('info'),
            Stat::make('Pending comments', number_format(Comment::query()->where('status', ContentStatus::Pending->value)->count()))
                ->description('Comments waiting for review')
                ->color('warning'),
            Stat::make('Open reports', number_format(Report::query()->where('status', ReportStatus::Pending->value)->count()))
                ->description('Reports that still need action')
                ->color('danger'),
            Stat::make('Open B2B leads', number_format(B2BLead::query()->whereIn('status', [B2BLeadStatus::New->value, B2BLeadStatus::InReview->value])->count()))
                ->description('Inbound leads that still need follow-up')
                ->color('warning'),
            Stat::make('Support-enabled concepts', number_format(FundingCampaign::query()->where('support_enabled', true)->count()))
                ->description('Concepts with an active support CTA')
                ->color('gray'),
            Stat::make('Banned users', number_format(User::query()->where('is_banned', true)->count()))
                ->description('Accounts currently blocked')
                ->color('gray'),
        ];
    }
}
