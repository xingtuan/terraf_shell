<?php

namespace App\Filament\Resources\ProductAttributeValues\Pages;

use App\Filament\Resources\ProductAttributeValues\ProductAttributeValueResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductAttributeValue extends EditRecord
{
    protected static string $resource = ProductAttributeValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
