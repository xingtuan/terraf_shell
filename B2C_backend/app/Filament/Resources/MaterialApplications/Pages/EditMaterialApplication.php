<?php

namespace App\Filament\Resources\MaterialApplications\Pages;

use App\Filament\Resources\MaterialApplications\MaterialApplicationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMaterialApplication extends EditRecord
{
    protected static string $resource = MaterialApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
