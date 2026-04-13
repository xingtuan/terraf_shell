<?php

namespace App\Filament\Resources\IdeaMedia\Pages;

use App\Filament\Resources\IdeaMedia\IdeaMediaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIdeaMedia extends ListRecords
{
    protected static string $resource = IdeaMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
