<?php

namespace App\Filament\Resources\MaterialStorySections\Pages;

use App\Filament\Resources\MaterialStorySections\MaterialStorySectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaterialStorySections extends ListRecords
{
    protected static string $resource = MaterialStorySectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
