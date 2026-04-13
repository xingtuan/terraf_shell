<?php

namespace App\Filament\Resources\UserViolations\Pages;

use App\Filament\Resources\UserViolations\UserViolationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserViolations extends ListRecords
{
    protected static string $resource = UserViolationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
