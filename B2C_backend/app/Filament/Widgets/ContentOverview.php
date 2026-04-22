<?php

namespace App\Filament\Widgets;

use App\Filament\Support\PanelAccess;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ContentOverview extends ChartWidget
{
    public array $dashboard = [];

    protected int|string|array $columnSpan = [
        'md' => 4,
        'xl' => 4,
    ];

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Publishing Cadence';

    protected string $color = 'success';

    protected ?string $maxHeight = '340px';

    public function getDescription(): ?string
    {
        $content = $this->dashboard['content'] ?? [];

        return number_format((int) ($content['published_total'] ?? 0)).' live content records | '
            .number_format((int) ($content['draft_total'] ?? 0)).' drafts still in progress';
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
        $content = $this->dashboard['content'] ?? [];

        return [
            'labels' => $content['labels_30d'] ?? [],
            'datasets' => [
                [
                    'label' => 'Materials',
                    'data' => $content['materials_series_30d'] ?? [],
                    'backgroundColor' => 'rgba(5, 150, 105, 0.72)',
                    'borderRadius' => 10,
                    'maxBarThickness' => 18,
                ],
                [
                    'label' => 'Articles',
                    'data' => $content['articles_series_30d'] ?? [],
                    'backgroundColor' => 'rgba(14, 165, 233, 0.72)',
                    'borderRadius' => 10,
                    'maxBarThickness' => 18,
                ],
                [
                    'label' => 'Homepage sections',
                    'data' => $content['sections_series_30d'] ?? [],
                    'backgroundColor' => 'rgba(217, 119, 6, 0.72)',
                    'borderRadius' => 10,
                    'maxBarThickness' => 18,
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
                    'stacked' => false,
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
