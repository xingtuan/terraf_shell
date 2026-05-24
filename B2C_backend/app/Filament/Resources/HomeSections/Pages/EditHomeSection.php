<?php

namespace App\Filament\Resources\HomeSections\Pages;

use App\Enums\PublishStatus;
use App\Filament\Resources\HomeSections\HomeSectionResource;
use App\Filament\Resources\HomeSections\Schemas\HomeSectionForm;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHomeSection extends EditRecord
{
    protected static string $resource = HomeSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = HomeSectionForm::applyPayloadState($data, $this->data, $this->record);
        $showOnFrontend = filter_var($this->data['show_on_frontend'] ?? false, FILTER_VALIDATE_BOOL);

        $data['status'] = $showOnFrontend
            ? PublishStatus::Published->value
            : PublishStatus::Draft->value;
        $data['is_seeded'] = false;

        return $data;
    }
}
