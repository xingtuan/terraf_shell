<?php

namespace App\Filament\Resources\ProductAttributeValues\Pages;

use App\Filament\Resources\ProductAttributeValues\ProductAttributeValueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductAttributeValues extends ListRecords
{
    protected static string $resource = ProductAttributeValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
