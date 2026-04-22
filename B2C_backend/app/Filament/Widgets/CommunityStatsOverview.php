<?php

namespace App\Filament\Widgets;

use App\Filament\Support\PanelAccess;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class CommunityStatsOverview extends ChartWidget
{
    public array $dashboard = [];

    protected int|string|array $columnSpan = [
        'md' => 4,
        'xl' => 4,
    ];

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Community Momentum';

    protected string $color = 'primary';

    protected ?string $maxHeight = '340px';

    public function getDescription(): ?string
    {
        $community = $this->dashboard['community'] ?? [];

        return ($community['top_category']['name'] ?? 'No category data')
            .' is currently leading with '
            .number_format((int) ($community['top_category']['concept_count'] ?? 0))
            .' approved concepts';
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
        $community = $this->dashboard['community'] ?? [];

        return [
            'labels' => $community['labels'] ?? [],
            'datasets' => [
                [
                    'type' => 'bar',
                    'label' => 'Approved concepts',
                    'data' => $community['concepts'] ?? [],
                    'yAxisID' => 'concepts',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.72)',
                    'borderRadius' => 10,
                    'maxBarThickness' => 22,
                ],
                [
                    'type' => 'line',
                    'label' => 'Engagement',
                    'data' => $community['engagement'] ?? [],
                    'yAxisID' => 'engagement',
                    'borderColor' => 'rgb(217, 119, 6)',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.16)',
                    'fill' => false,
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
                'concepts' => [
                    'position' => 'left',
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
                'engagement' => [
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
        return PanelAccess::isStaff();
    }
}
