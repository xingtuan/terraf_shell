<?php

namespace App\Filament\Resources\UserViolations\Pages;

use App\Filament\Resources\UserViolations\UserViolationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUserViolation extends ViewRecord
{
    protected static string $resource = UserViolationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
