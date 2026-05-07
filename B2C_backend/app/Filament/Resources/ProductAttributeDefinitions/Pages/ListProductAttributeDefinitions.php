<?php

namespace App\Filament\Resources\ProductAttributeDefinitions\Pages;

use App\Filament\Resources\ProductAttributeDefinitions\ProductAttributeDefinitionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductAttributeDefinitions extends ListRecords
{
    protected static string $resource = ProductAttributeDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
