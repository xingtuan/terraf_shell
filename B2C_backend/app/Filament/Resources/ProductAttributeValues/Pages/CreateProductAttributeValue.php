<?php

namespace App\Filament\Resources\ProductAttributeValues\Pages;

use App\Filament\Resources\ProductAttributeValues\ProductAttributeValueResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductAttributeValue extends CreateRecord
{
    protected static string $resource = ProductAttributeValueResource::class;
}
