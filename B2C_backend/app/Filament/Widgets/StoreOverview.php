<?php

namespace App\Filament\Widgets;

use App\Filament\Support\PanelAccess;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class StoreOverview extends ChartWidget
{
    public array $dashboard = [];

    protected int|string|array $columnSpan = [
        'md' => 4,
        'xl' => 8,
    ];

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Commerce Performance';

    protected string $color = 'warning';

    protected ?string $maxHeight = '340px';

    public function getDescription(): ?string
    {
        $commerce = $this->dashboard['commerce'] ?? [];

        return '$'.number_format((float) ($commerce['revenue_30d'] ?? 0), 2)
            .' booked in the last 30 days | '
            .number_format((int) ($commerce['backlog_count'] ?? 0))
            .' orders still in fulfilment | '
            .number_format((int) ($commerce['unpaid_count'] ?? 0))
            .' awaiting payment review';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $commerce = $this->dashboard['commerce'] ?? [];

        return [
            'labels' => $commerce['labels_30d'] ?? [],
            'datasets' => [
                [
                    'type' => 'bar',
                    'label' => 'Revenue (USD)',
                    'data' => $commerce['revenue_series_30d'] ?? [],
                    'yAxisID' => 'revenue',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.18)',
                    'borderColor' => 'rgb(217, 119, 6)',
                    'borderRadius' => 10,
                    'maxBarThickness' => 20,
                ],
                [
                    'type' => 'line',
                    'label' => 'Orders',
                    'data' => $commerce['orders_series_30d'] ?? [],
                    'yAxisID' => 'orders',
                    'borderColor' => 'rgb(5, 150, 105)',
                    'backgroundColor' => 'rgba(5, 150, 105, 0.18)',
                    'fill' => true,
                    'tension' => 0.35,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 3,
                ],
            ],
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'revenue' => [
                    'position' => 'left',
                    'beginAtZero' => true,
                ],
                'orders' => [
                    'position' => 'right',
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        return PanelAccess::isAdmin();
    }
}
