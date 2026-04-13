<?php

namespace App\Filament\Resources\MaterialApplications\Pages;

use App\Filament\Resources\MaterialApplications\MaterialApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaterialApplications extends ListRecords
{
    protected static string $resource = MaterialApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
