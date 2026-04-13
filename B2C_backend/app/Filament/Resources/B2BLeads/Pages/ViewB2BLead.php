<?php

namespace App\Filament\Resources\B2BLeads\Pages;

use App\Filament\Resources\B2BLeads\B2BLeadResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewB2BLead extends ViewRecord
{
    protected static string $resource = B2BLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
