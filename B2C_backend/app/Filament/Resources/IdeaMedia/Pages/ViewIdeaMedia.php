<?php

namespace App\Filament\Resources\IdeaMedia\Pages;

use App\Filament\Resources\IdeaMedia\IdeaMediaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewIdeaMedia extends ViewRecord
{
    protected static string $resource = IdeaMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
