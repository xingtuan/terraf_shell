<?php

namespace App\Filament\Resources\MaterialSpecs\Pages;

use App\Filament\Resources\MaterialSpecs\MaterialSpecResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaterialSpecs extends ListRecords
{
    protected static string $resource = MaterialSpecResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
