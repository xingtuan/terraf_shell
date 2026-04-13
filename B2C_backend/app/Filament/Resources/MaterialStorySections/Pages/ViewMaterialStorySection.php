<?php

namespace App\Filament\Resources\MaterialStorySections\Pages;

use App\Filament\Resources\MaterialStorySections\MaterialStorySectionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMaterialStorySection extends ViewRecord
{
    protected static string $resource = MaterialStorySectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
