<?php

namespace App\Filament\Resources\ProductAttributeDefinitions\Pages;

use App\Filament\Resources\ProductAttributeDefinitions\ProductAttributeDefinitionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductAttributeDefinition extends EditRecord
{
    protected static string $resource = ProductAttributeDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
