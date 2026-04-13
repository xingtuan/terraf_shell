<?php

namespace App\Filament\Resources\MaterialSpecs\Pages;

use App\Filament\Resources\MaterialSpecs\MaterialSpecResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMaterialSpec extends EditRecord
{
    protected static string $resource = MaterialSpecResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
