<?php

namespace App\Filament\Resources\MaterialProperties\Pages;

use App\Filament\Resources\MaterialProperties\MaterialPropertyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMaterialProperty extends EditRecord
{
    protected static string $resource = MaterialPropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
