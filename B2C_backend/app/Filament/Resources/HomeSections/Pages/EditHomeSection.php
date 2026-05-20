<?php

namespace App\Filament\Resources\HomeSections\Pages;

use App\Filament\Resources\HomeSections\HomeSectionResource;
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
        // Filament's form dehydration prunes state to only validated keys, which
        // strips locale fields (e.g. payload.home_translations.*) that have no
        // validation rules. For the footer section we read the full payload
        // directly from the Livewire component state, which holds every key
        // correctly after afterStateHydrated converted it to an associative array.
        if (($data['key'] ?? null) === 'footer') {
            $livewirePayload = $this->data['payload'] ?? null;
            if (is_array($livewirePayload)) {
                $data['payload'] = $livewirePayload;
            }
        }

        return $data;
    }
}
