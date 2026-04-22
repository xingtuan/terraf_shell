<?php

namespace App\Filament\Widgets;

use App\Filament\Support\PanelAccess;
use Filament\Widgets\Widget;

class AnalyticsSnapshot extends Widget
{
    public array $dashboard = [];

    protected string $view = 'filament.widgets.analytics-snapshot';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'hero' => $this->dashboard['hero'] ?? [],
            'generatedAt' => $this->dashboard['generated_at'] ?? now()->toISOString(),
        ];
    }

    public static function canView(): bool
    {
        return PanelAccess::isAdmin();
    }
}
