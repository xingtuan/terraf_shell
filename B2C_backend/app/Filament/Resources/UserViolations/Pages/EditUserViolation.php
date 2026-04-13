<?php

namespace App\Filament\Resources\UserViolations\Pages;

use App\Filament\Resources\UserViolations\UserViolationResource;
use App\Filament\Support\PanelAccess;
use App\Services\GovernanceService;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUserViolation extends EditRecord
{
    protected static string $resource = UserViolationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(GovernanceService::class)->updateViolationStatus(
            $record,
            PanelAccess::user(),
            $data['status'],
            $data['resolution_note'] ?? null,
        );
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Violation updated';
    }
}
