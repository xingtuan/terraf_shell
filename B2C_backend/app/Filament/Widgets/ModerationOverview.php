<?php

namespace App\Filament\Widgets;

use App\Filament\Support\PanelAccess;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ModerationOverview extends ChartWidget
{
    public array $dashboard = [];

    protected int|string|array $columnSpan = [
        'md' => 4,
        'xl' => 4,
    ];

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Moderation Pressure';

    protected string $color = 'danger';

    protected ?string $maxHeight = '340px';

    public function getDescription(): ?string
    {
        $moderation = $this->dashboard['moderation'] ?? [];

        return number_format((int) ($moderation['pending_posts'] ?? 0)).' concepts | '
            .number_format((int) ($moderation['pending_comments'] ?? 0)).' comments | '
            .number_format((int) ($moderation['open_reports'] ?? 0)).' open reports';
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
        $moderation = $this->dashboard['moderation'] ?? [];

        return [
            'labels' => $moderation['labels_14d'] ?? [],
            'datasets' => [
                [
                    'label' => 'Pending concepts',
                    'data' => $moderation['pending_posts_series_14d'] ?? [],
                    'backgroundColor' => 'rgba(245, 158, 11, 0.72)',
                    'borderRadius' => 10,
                    'maxBarThickness' => 18,
                ],
                [
                    'label' => 'Pending comments',
                    'data' => $moderation['pending_comments_series_14d'] ?? [],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.72)',
                    'borderRadius' => 10,
                    'maxBarThickness' => 18,
                ],
                [
                    'label' => 'Open reports',
                    'data' => $moderation['open_reports_series_14d'] ?? [],
                    'backgroundColor' => 'rgba(220, 38, 38, 0.72)',
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
        return PanelAccess::isStaff();
    }
}
