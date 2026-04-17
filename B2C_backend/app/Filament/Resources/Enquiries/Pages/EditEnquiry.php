<?php

namespace App\Filament\Resources\Enquiries\Pages;

use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Filament\Support\PanelAccess;
use App\Services\InquiryService;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditEnquiry extends EditRecord
{
    protected static string $resource = EnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(InquiryService::class)->updateForAdmin(
            $record,
            $data,
            PanelAccess::user(),
        );
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Enquiry updated';
    }
}
