<?php

namespace App\Filament\Resources\ModerationLogs\Pages;

use App\Filament\Resources\ModerationLogs\ModerationLogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewModerationLog extends ViewRecord
{
    protected static string $resource = ModerationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
