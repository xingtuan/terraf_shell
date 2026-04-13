<?php

namespace App\Filament\Resources\FundingCampaigns\Pages;

use App\Filament\Resources\FundingCampaigns\FundingCampaignResource;
use App\Filament\Support\PanelAccess;
use App\Services\FundingCampaignService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditFundingCampaign extends EditRecord
{
    protected static string $resource = FundingCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('deleteCampaign')
                ->label('Delete')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (): void {
                    $record = $this->getRecord();

                    app(FundingCampaignService::class)->deleteForPost(
                        $record->post,
                        PanelAccess::user(),
                    );

                    Notification::make()
                        ->title('Funding campaign deleted')
                        ->success()
                        ->send();

                    $this->redirect(FundingCampaignResource::getUrl('index'));
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        unset($data['post_id']);

        return app(FundingCampaignService::class)->upsertForPost(
            $record->post,
            $data,
            PanelAccess::user(),
        );
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Funding campaign updated';
    }
}
