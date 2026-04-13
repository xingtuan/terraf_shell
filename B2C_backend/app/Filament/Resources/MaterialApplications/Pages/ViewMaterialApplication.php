<?php

namespace App\Filament\Resources\MaterialApplications\Pages;

use App\Filament\Resources\MaterialApplications\MaterialApplicationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMaterialApplication extends ViewRecord
{
    protected static string $resource = MaterialApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
