<?php

namespace App\Filament\Widgets;

use App\Filament\Support\PanelAccess;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class LeadOperationsOverview extends ChartWidget
{
    public array $dashboard = [];

    protected int|string|array $columnSpan = [
        'md' => 4,
        'xl' => 4,
    ];

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Lead Flow';

    protected string $color = 'info';

    protected ?string $maxHeight = '340px';

    public function getDescription(): ?string
    {
        $leads = $this->dashboard['leads'] ?? [];

        return number_format((int) ($leads['open_enquiries'] ?? 0)).' open enquiries | '
            .number_format((int) ($leads['unassigned_enquiries'] ?? 0)).' still unassigned | '
            .number_format((int) ($leads['qualified_leads'] ?? 0)).' qualified opportunities';
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $leads = $this->dashboard['leads'] ?? [];

        return [
            'labels' => $leads['labels_30d'] ?? [],
            'datasets' => [
                [
                    'label' => 'Enquiries',
                    'data' => $leads['enquiries_series_30d'] ?? [],
                    'borderColor' => 'rgb(14, 165, 233)',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.14)',
                    'fill' => true,
                    'tension' => 0.3,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 3,
                ],
                [
                    'label' => 'B2B opportunities',
                    'data' => $leads['opportunities_series_30d'] ?? [],
                    'borderColor' => 'rgb(217, 119, 6)',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.14)',
                    'fill' => true,
                    'tension' => 0.3,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 3,
                ],
                [
                    'label' => 'Qualified',
                    'data' => $leads['qualified_series_30d'] ?? [],
                    'borderColor' => 'rgb(5, 150, 105)',
                    'backgroundColor' => 'rgba(5, 150, 105, 0.12)',
                    'fill' => false,
                    'tension' => 0.3,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 3,
                    'borderDash' => [5, 5],
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
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
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
