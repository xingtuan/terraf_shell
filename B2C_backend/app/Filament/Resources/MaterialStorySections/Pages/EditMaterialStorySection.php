<?php

namespace App\Filament\Resources\MaterialStorySections\Pages;

use App\Filament\Resources\MaterialStorySections\MaterialStorySectionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMaterialStorySection extends EditRecord
{
    protected static string $resource = MaterialStorySectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
