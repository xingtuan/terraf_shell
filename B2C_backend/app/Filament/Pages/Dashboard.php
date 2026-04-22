<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AnalyticsSnapshot;
use App\Filament\Widgets\CommunityStatsOverview;
use App\Filament\Widgets\ContentOverview;
use App\Filament\Widgets\LeadOperationsOverview;
use App\Filament\Widgets\ModerationOverview;
use App\Filament\Widgets\RecentActivity;
use App\Filament\Widgets\RecentLeads;
use App\Filament\Widgets\RecentOrders;
use App\Filament\Widgets\StoreOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    public function getColumns(): int|string|array
    {
        return [
            'default' => 1,
            'sm'      => 2,
            'lg'      => 3,
        ];
    }

    public function getWidgets(): array
    {
        return [
            StoreOverview::class,
            LeadOperationsOverview::class,
            ContentOverview::class,
            ModerationOverview::class,
            CommunityStatsOverview::class,
            AnalyticsSnapshot::class,
            RecentOrders::class,
            RecentLeads::class,
            RecentActivity::class,
        ];
    }
}
