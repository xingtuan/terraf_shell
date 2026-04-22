<?php

namespace App\Filament\Widgets;

use App\Enums\AccountStatus;
use App\Enums\ContentStatus;
use App\Enums\ReportStatus;
use App\Filament\Pages\ModerationQueue;
use App\Filament\Resources\Reports\ReportResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\PanelAccess;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ModerationOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Moderation Overview';

    protected ?string $description = 'Community backlog and governance signals for staff review.';

    protected function getStats(): array
    {
        $pendingPosts = Post::query()->where('status', ContentStatus::Pending->value)->count();
        $pendingComments = Comment::query()->where('status', ContentStatus::Pending->value)->count();
        $openReports = Report::query()->where('status', ReportStatus::Pending->value)->count();
        $restrictedUsers = User::query()
            ->where(function ($query): void {
                $query
                    ->where('is_banned', true)
                    ->orWhere('account_status', AccountStatus::Restricted->value);
            })
            ->count();

        return [
            Stat::make('Pending concepts', number_format($pendingPosts))
                ->description('Posts waiting for moderation')
                ->color($pendingPosts > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-light-bulb')
                ->url(ModerationQueue::getUrl()),
            Stat::make('Pending comments', number_format($pendingComments))
                ->description('Replies waiting for moderation')
                ->color($pendingComments > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->url(ModerationQueue::getUrl()),
            Stat::make('Open reports', number_format($openReports))
                ->description('User reports that still need action')
                ->color($openReports > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-flag')
                ->url(ReportResource::getUrl()),
            Stat::make('Restricted users', number_format($restrictedUsers))
                ->description('Banned or restricted community accounts')
                ->color('gray')
                ->icon('heroicon-o-no-symbol')
                ->url(UserResource::getUrl()),
        ];
    }

    public static function canView(): bool
    {
        return PanelAccess::isStaff();
    }
}
