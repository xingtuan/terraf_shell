<?php

namespace App\Filament\Pages;

use App\Filament\Support\PanelAccess;
use App\Filament\Widgets\AnalyticsSnapshot;
use App\Filament\Widgets\CommunityStatsOverview;
use App\Filament\Widgets\ContentOverview;
use App\Filament\Widgets\ExecutiveKpiOverview;
use App\Filament\Widgets\LeadOperationsOverview;
use App\Filament\Widgets\ModerationOverview;
use App\Filament\Widgets\RecentActivity;
use App\Filament\Widgets\RecentLeads;
use App\Filament\Widgets\RecentOrders;
use App\Filament\Widgets\StoreOverview;
use App\Services\DashboardInsightsService;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Operations Dashboard';

    protected ?string $subheading = 'Daily visibility across commerce, leads, content, and community operations.';

    protected function getHeaderWidgets(): array
    {
        return [
            AnalyticsSnapshot::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 4,
            'xl' => 12,
        ];
    }

    public function getWidgets(): array
    {
        return [
            ExecutiveKpiOverview::class,
            StoreOverview::class,
            LeadOperationsOverview::class,
            ModerationOverview::class,
            ContentOverview::class,
            CommunityStatsOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            RecentOrders::class,
            RecentLeads::class,
            RecentActivity::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return [
            'md' => 4,
            'xl' => 12,
        ];
    }

    public function getWidgetData(): array
    {
        return [
            'dashboard' => app(DashboardInsightsService::class)->snapshot(
                includeAdmin: PanelAccess::isAdmin(),
            ),
        ];
    }
}
