<?php

namespace App\Filament\Resources\UserViolations\Actions;

use App\Enums\UserViolationStatus;
use App\Enums\UserViolationType;
use App\Filament\Support\PanelAccess;
use App\Models\User;
use App\Models\UserViolation;
use App\Services\AdminModerationService;
use App\Services\GovernanceService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class UserViolationActions
{
    public static function resolveOnly(): Action
    {
        return Action::make('resolveViolationOnly')
            ->label('Resolve violation only')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (UserViolation $record): bool => PanelAccess::isStaff()
                && $record->status === UserViolationStatus::Open->value)
            ->schema([
                Textarea::make('resolution_note')
                    ->label(__('admin.ui.resolution_note'))
                    ->required()
                    ->rows(3),
            ])
            ->action(function (UserViolation $record, array $data): void {
                app(GovernanceService::class)->updateViolationStatus(
                    $record,
                    PanelAccess::user(),
                    UserViolationStatus::Resolved->value,
                    $data['resolution_note'],
                );

                self::success(__('admin.ui.violation_updated'));
            })
            ->requiresConfirmation();
    }

    public static function resolveAndRestoreAccount(): Action
    {
        return Action::make('resolveAndRestoreAccount')
            ->label('Resolve and restore account')
            ->icon('heroicon-o-arrow-path')
            ->color('success')
            ->visible(function (UserViolation $record): bool {
                $actor = PanelAccess::user();

                return PanelAccess::isAdmin()
                    && $actor instanceof User
                    && $record->user_id !== $actor->id
                    && $record->status === UserViolationStatus::Open->value
                    && in_array($record->type, [
                        UserViolationType::AccountBanned->value,
                        UserViolationType::AccountRestricted->value,
                    ], true);
            })
            ->schema([
                Textarea::make('resolution_note')
                    ->label(__('admin.ui.resolution_note'))
                    ->required()
                    ->rows(3),
            ])
            ->action(function (UserViolation $record, array $data): void {
                app(AdminModerationService::class)->resolveAccountViolationAndRestore(
                    $record,
                    PanelAccess::user(),
                    $data['resolution_note'],
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
