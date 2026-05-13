<?php

namespace App\Filament\Resources\Users\Actions;

use App\Enums\AccountStatus;
use App\Filament\Support\PanelAccess;
use App\Models\User;
use App\Services\AdminModerationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class UserModerationActions
{
    public static function restrict(): Action
    {
        return Action::make('restrict')
            ->label(__('admin.ui.restrict'))
            ->icon('heroicon-o-exclamation-triangle')
            ->color('warning')
            ->visible(function (User $record): bool {
                $actor = PanelAccess::user();

                if (! ($actor instanceof User) || ! $record->isActive() || $record->is($actor)) {
                    return false;
                }

                if (PanelAccess::isAdmin()) {
                    return true;
                }

                return PanelAccess::isModerator() && ! $record->isStaff();
            })
            ->schema([
                Textarea::make('reason')
                    ->label(__('admin.ui.restriction_reason'))
                    ->required()
                    ->rows(3),
            ])
            ->action(function (User $record, array $data): void {
                app(AdminModerationService::class)->updateAccountStatus(
                    $record,
                    AccountStatus::Restricted->value,
                    PanelAccess::user(),
                    $data['reason'],
                );

                self::success(__('admin.ui.user_restricted_successfully'));
            })
            ->requiresConfirmation();
    }

    public static function ban(): Action
    {
        return Action::make('ban')
            ->label(__('admin.ui.ban'))
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->visible(function (User $record): bool {
                $actor = PanelAccess::user();

                return PanelAccess::isAdmin()
                    && $actor instanceof User
                    && ! $record->is($actor)
                    && ! $record->isBanned();
            })
            ->schema([
                Textarea::make('reason')
                    ->label(__('admin.ui.ban_reason'))
                    ->required()
                    ->rows(3),
            ])
            ->action(function (User $record, array $data): void {
                app(AdminModerationService::class)->updateAccountStatus(
                    $record,
                    AccountStatus::Banned->value,
                    PanelAccess::user(),
                    $data['reason'],
                );

                self::success(__('admin.ui.user_banned_successfully'));
            })
            ->requiresConfirmation();
    }

    public static function restoreActive(): Action
    {
        return Action::make('restoreActive')
            ->label('Restore active / Unban')
            ->icon('heroicon-o-arrow-path')
            ->color('success')
            ->visible(function (User $record): bool {
                $actor = PanelAccess::user();

                return PanelAccess::isAdmin()
                    && $actor instanceof User
                    && ! $record->is($actor)
                    && ($record->isBanned() || $record->isRestricted());
            })
            ->schema([
                Textarea::make('reason')
                    ->label(__('admin.fields.reason'))
                    ->rows(3),
            ])
            ->action(function (User $record, array $data): void {
                app(AdminModerationService::class)->updateAccountStatus(
                    $record,
                    AccountStatus::Active->value,
                    PanelAccess::user(),
                    $data['reason'] ?? 'Account restored by admin.',
                );

                self::success(__('admin.ui.user_reactivated_successfully'));
            })
            ->requiresConfirmation();
    }

    private static function success(string $title): void
    {
        Notification::make()
            ->title($title)
            ->success()
            ->send();
    }
}
