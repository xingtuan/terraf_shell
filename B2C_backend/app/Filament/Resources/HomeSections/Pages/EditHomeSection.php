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
        file_put_contents(
            public_path('_debug_payload.json'),
            json_encode([
                'key'       => $data['key'] ?? null,
                'payload'   => $data['payload'] ?? 'NOT SET',
                'timestamp' => date('c'),
            ], JSON_PRETTY_PRINT)
        );

        return $data;
    }
}
