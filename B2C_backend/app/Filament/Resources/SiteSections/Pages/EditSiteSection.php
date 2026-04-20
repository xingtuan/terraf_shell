<?php

namespace App\Filament\Resources\SiteSections\Pages;

use App\Filament\Resources\SiteSections\SiteSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSiteSection extends EditRecord
{
    protected static string $resource = SiteSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
