<?php

namespace App\Filament\Resources\B2BLeads\Pages;

use App\Filament\Resources\B2BLeads\B2BLeadResource;
use App\Filament\Support\PanelAccess;
use App\Services\B2BLeadService;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditB2BLead extends EditRecord
{
    protected static string $resource = B2BLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(B2BLeadService::class)->updateForAdmin(
            $record,
            $data,
            PanelAccess::user(),
        );
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'B2B lead updated';
    }
}
