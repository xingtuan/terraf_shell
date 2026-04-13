<?php

namespace App\Filament\Resources\UserViolations\Pages;

use App\Filament\Resources\UserViolations\UserViolationResource;
use App\Filament\Support\PanelAccess;
use App\Models\User;
use App\Services\GovernanceService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUserViolation extends CreateRecord
{
    protected static string $resource = UserViolationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::query()->findOrFail((int) $data['user_id']);

        return app(GovernanceService::class)->storeManualViolation(
            $user,
            PanelAccess::user(),
            $data,
        );
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Violation recorded';
    }
}
