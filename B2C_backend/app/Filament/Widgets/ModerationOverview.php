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

    protected ?string $heading = null;

    protected ?string $description = 'Community backlog and governance signals for staff review.';

    protected function getHeading(): ?string
    {
        return __('admin.widgets.moderation_overview');
    }

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
                ->description(__('admin.ui.posts_waiting_for_moderation'))
                ->color($pendingPosts > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-light-bulb')
                ->url(ModerationQueue::getUrl()),
            Stat::make('Pending comments', number_format($pendingComments))
                ->description(__('admin.ui.replies_waiting_for_moderation'))
                ->color($pendingComments > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->url(ModerationQueue::getUrl()),
            Stat::make('Open reports', number_format($openReports))
                ->description(__('admin.ui.user_reports_that_still_need_action'))
                ->color($openReports > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-flag')
                ->url(ReportResource::getUrl()),
            Stat::make('Restricted users', number_format($restrictedUsers))
                ->description(__('admin.ui.banned_or_restricted_community_accounts'))
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
