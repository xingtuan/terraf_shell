<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use App\Services\OrderService;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    private ?string $previousStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $status = $this->record->status;
        $this->previousStatus = $status instanceof OrderStatus ? $status->value : (string) $status;
    }

    protected function afterSave(): void
    {
        $status = $this->record->status instanceof OrderStatus ? $this->record->status->value : (string) $this->record->status;

        if ($this->previousStatus !== null && $this->previousStatus !== $status) {
            app(OrderService::class)->dispatchStatusChangedEmail(
                $this->record->fresh(['user', 'items.product']),
                $this->previousStatus,
            );
        }
    }
}
