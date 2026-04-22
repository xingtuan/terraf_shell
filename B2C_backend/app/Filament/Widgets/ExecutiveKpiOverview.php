<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\PanelAccess;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExecutiveKpiOverview extends StatsOverviewWidget
{
    public array $dashboard = [];

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Executive Summary';

    protected ?string $description = 'The metrics that should shape today’s operating decisions.';

    protected function getColumns(): int|array|null
    {
        return [
            'md' => 2,
            'xl' => 6,
        ];
    }

    protected function getStats(): array
    {
        $kpis = $this->dashboard['kpis'] ?? [];
        $revenueChange = $this->percentageChange(
            (float) ($kpis['revenue']['value'] ?? 0),
            (float) ($kpis['revenue']['previous'] ?? 0),
        );

        return [
            Stat::make('Open orders', number_format((int) ($kpis['open_orders']['value'] ?? 0)))
                ->description(number_format((int) ($kpis['open_orders']['recent'] ?? 0)).' created in the last 14 days')
                ->descriptionColor('warning')
                ->descriptionIcon('heroicon-m-arrow-trending-up', IconPosition::Before)
                ->icon('heroicon-o-shopping-bag')
                ->color('warning')
                ->chart($kpis['open_orders']['chart'] ?? [])
                ->url(OrderResource::getUrl()),
            Stat::make('Revenue (30d)', '$'.number_format((float) ($kpis['revenue']['value'] ?? 0), 2))
                ->description($this->formatPercentage($revenueChange).' vs previous 30 days')
                ->descriptionColor($revenueChange >= 0 ? 'success' : 'danger')
                ->descriptionIcon(
                    $revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
                    IconPosition::Before,
                )
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->chart($kpis['revenue']['chart'] ?? []),
            Stat::make('Open leads', number_format((int) ($kpis['open_leads']['value'] ?? 0)))
                ->description(number_format((int) ($kpis['open_leads']['recent'] ?? 0)).' new submissions in the last 14 days')
                ->descriptionColor('info')
                ->descriptionIcon('heroicon-m-arrow-trending-up', IconPosition::Before)
                ->icon('heroicon-o-briefcase')
                ->color('info')
                ->chart($kpis['open_leads']['chart'] ?? []),
            Stat::make('Pending moderation', number_format((int) ($kpis['moderation_backlog']['value'] ?? 0)))
                ->description(number_format((int) ($kpis['moderation_backlog']['recent'] ?? 0)).' queue items entered in the last 14 days')
                ->descriptionColor('danger')
                ->descriptionIcon('heroicon-m-exclamation-triangle', IconPosition::Before)
                ->icon('heroicon-o-shield-exclamation')
                ->color('danger')
                ->chart($kpis['moderation_backlog']['chart'] ?? []),
            Stat::make('Published products', number_format((int) ($kpis['published_products']['value'] ?? 0)))
                ->description(number_format((int) ($kpis['published_products']['recent'] ?? 0)).' launched in the last 30 days')
                ->descriptionColor('success')
                ->descriptionIcon('heroicon-m-rocket-launch', IconPosition::Before)
                ->icon('heroicon-o-cube')
                ->color('success')
                ->chart($kpis['published_products']['chart'] ?? [])
                ->url(ProductResource::getUrl()),
            Stat::make('Stock alerts', number_format((int) ($kpis['stock_alerts']['value'] ?? 0)))
                ->description(number_format((int) ($kpis['stock_alerts']['sold_out'] ?? 0)).' sold out right now')
                ->descriptionColor(((int) ($kpis['stock_alerts']['value'] ?? 0)) > 0 ? 'danger' : 'success')
                ->descriptionIcon(
                    ((int) ($kpis['stock_alerts']['value'] ?? 0)) > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle',
                    IconPosition::Before,
                )
                ->icon('heroicon-o-exclamation-triangle')
                ->color(((int) ($kpis['stock_alerts']['value'] ?? 0)) > 0 ? 'danger' : 'success')
                ->url(ProductResource::getUrl()),
        ];
    }

    public static function canView(): bool
    {
        return PanelAccess::isAdmin();
    }

    private function percentageChange(float $current, float $previous): float
    {
        if ($previous === 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function formatPercentage(float $value): string
    {
        return ($value >= 0 ? '+' : '').number_format($value, 1).'%';
    }
}
