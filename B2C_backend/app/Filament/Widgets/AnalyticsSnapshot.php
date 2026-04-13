<?php

namespace App\Filament\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\Widget;

class AnalyticsSnapshot extends Widget
{
    protected string $view = 'filament.widgets.analytics-snapshot';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'overview' => app(AnalyticsService::class)->overview(5),
        ];
    }
}
