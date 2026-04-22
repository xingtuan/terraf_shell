<?php

namespace App\Filament\Widgets;

use App\Filament\Support\PanelAccess;
use Filament\Widgets\Widget;

class RecentActivity extends Widget
{
    public array $dashboard = [];

    protected string $view = 'filament.widgets.recent-activity';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $items = collect($this->dashboard['activity'] ?? []);

        if (! PanelAccess::isAdmin()) {
            $items = $items->where('scope', 'staff');
        }

        return [
            'items' => $items->values()->all(),
            'isAdmin' => PanelAccess::isAdmin(),
        ];
    }

    public static function canView(): bool
    {
        return PanelAccess::isStaff();
    }
}
