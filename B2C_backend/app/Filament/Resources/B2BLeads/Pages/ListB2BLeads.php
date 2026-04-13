<?php

namespace App\Filament\Resources\B2BLeads\Pages;

use App\Filament\Resources\B2BLeads\B2BLeadResource;
use App\Services\B2BLeadService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListB2BLeads extends ListRecords
{
    protected static string $resource = B2BLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => app(B2BLeadService::class)->exportForAdmin([])),
        ];
    }
}
