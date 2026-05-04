<?php

namespace App\Filament\Resources\EmailTemplates\Pages;

use App\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Filament\Support\PanelAccess;
use Filament\Resources\Pages\EditRecord;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by_id'] = PanelAccess::user()?->id;

        return $data;
    }
}
