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
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListUserNotifications extends ListRecords
{
    protected static string $resource = UserNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('announce')
                ->label(__('admin.ui.broadcast_announcement'))
                ->icon('heroicon-o-megaphone')
                ->visible(fn (): bool => PanelAccess::isAdmin())
                ->schema([
                    TextInput::make('title')
                        ->label(__('admin.ui.title'))
                        ->required()
                        ->maxLength(150),
                    Textarea::make('body')
                        ->label(__('admin.ui.body'))
                        ->required()
                        ->rows(5),
                    TextInput::make('action_url')
                        ->label(__('admin.ui.action_url'))
                        ->url()
                        ->maxLength(2048),
                    Select::make('roles')
                        ->label(__('admin.ui.audience_roles'))
                        ->multiple()
                        ->options([
                            UserRole::Creator->value => UserRole::Creator->label(),
                            UserRole::SmePartner->value => UserRole::SmePartner->label(),
                        ])
                        ->helperText(__('admin.ui.leave_blank_to_send_to_all_active_users')),
                    Toggle::make('send_email')
                        ->label(__('admin.ui.also_send_email'))
                        ->helperText(__('admin.ui.email_sending_still_respects_the_email_center_system_announcement_switch')),
                ])
                ->action(function (array $data): void {
                    $count = app(NotificationService::class)->broadcastSystemAnnouncement(
                        PanelAccess::user(),
                        $data['title'],
                        $data['body'],
                        $data['action_url'] ?? null,
                        $data['roles'] ?? [],
                        (bool) ($data['send_email'] ?? false),
                    );

                    app(GovernanceService::class)->recordAdminAction(
                        PanelAccess::user(),
                        'notification.system_announcement_sent',
                        __('admin.ui.system_announcement_sent_from_admin'),
                        [
                            'title' => $data['title'],
                            'audience_roles' => $data['roles'] ?? [],
                            'recipients' => $count,
                            'send_email' => (bool) ($data['send_email'] ?? false),
                        ],
                    );

                    Notification::make()
                        ->title(__('admin.ui.announcement_delivered_to_users', ['count' => $count]))
                        ->success()
                        ->send();
                }),
        ];
    }
}
