<?php

namespace App\Filament\Resources\HomeSections\Pages;

use App\Enums\PublishStatus;
use App\Filament\Resources\HomeSections\HomeSectionResource;
use App\Filament\Resources\HomeSections\Schemas\HomeSectionForm;
use Filament\Resources\Pages\CreateRecord;

class CreateHomeSection extends CreateRecord
{
    protected static string $resource = HomeSectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = HomeSectionForm::applyPayloadState($data, $this->data);
        $showOnFrontend = filter_var($this->data['show_on_frontend'] ?? false, FILTER_VALIDATE_BOOL);

        $data['status'] = $showOnFrontend
            ? PublishStatus::Published->value
            : PublishStatus::Draft->value;

        return $data;
    }
}
