<?php

namespace App\Filament\Resources\MaterialSpecs\Pages;

use App\Filament\Resources\MaterialSpecs\MaterialSpecResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMaterialSpec extends ViewRecord
{
    protected static string $resource = MaterialSpecResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
