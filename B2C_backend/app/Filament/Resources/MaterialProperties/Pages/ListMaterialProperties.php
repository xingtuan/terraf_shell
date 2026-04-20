<?php

namespace App\Filament\Resources\MaterialProperties\Pages;

use App\Filament\Resources\MaterialProperties\MaterialPropertyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaterialProperties extends ListRecords
{
    protected static string $resource = MaterialPropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
