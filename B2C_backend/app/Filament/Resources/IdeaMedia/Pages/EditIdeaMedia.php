<?php

namespace App\Filament\Resources\IdeaMedia\Pages;

use App\Filament\Resources\IdeaMedia\IdeaMediaResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditIdeaMedia extends EditRecord
{
    protected static string $resource = IdeaMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
