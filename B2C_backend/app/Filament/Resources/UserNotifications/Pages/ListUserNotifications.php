<?php

namespace App\Filament\Resources\UserNotifications\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\UserNotifications\UserNotificationResource;
use App\Filament\Support\PanelAccess;
use App\Services\GovernanceService;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListUserNotifications extends ListRecords
{
    protected static string $resource = UserNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('announce')
                ->label('Broadcast Announcement')
                ->icon('heroicon-o-megaphone')
                ->visible(fn (): bool => PanelAccess::isAdmin())
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(150),
                    Textarea::make('body')
                        ->required()
                        ->rows(5),
                    TextInput::make('action_url')
                        ->label('Action URL')
                        ->url()
                        ->maxLength(2048),
                    Select::make('roles')
                        ->label('Audience roles')
                        ->multiple()
                        ->options([
                            UserRole::Creator->value => UserRole::Creator->label(),
                            UserRole::SmePartner->value => UserRole::SmePartner->label(),
                        ])
                        ->helperText('Leave blank to send to all active users.'),
                ])
                ->action(function (array $data): void {
                    $count = app(NotificationService::class)->broadcastSystemAnnouncement(
                        PanelAccess::user(),
                        $data['title'],
                        $data['body'],
                        $data['action_url'] ?? null,
                        $data['roles'] ?? [],
                    );

                    app(GovernanceService::class)->recordAdminAction(
                        PanelAccess::user(),
                        'notification.system_announcement_sent',
                        'System announcement sent from the admin panel.',
                        [
                            'title' => $data['title'],
                            'audience_roles' => $data['roles'] ?? [],
                            'recipients' => $count,
                        ],
                    );

                    Notification::make()
                        ->title("Announcement delivered to {$count} users.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
