<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Filament\Support\PanelAccess;
use App\Models\User;
use App\Services\AdminModerationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('profile.avatar_url')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl('https://placehold.co/64x64?text=U'),
                TextColumn::make('name')
                    ->label('User')
                    ->description(fn (User $record): string => '@'.$record->username)
                    ->searchable(['name', 'username', 'email'])
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => UserRole::tryFrom($state)?->label() ?? ucfirst($state))
                    ->color(fn (string $state): string => UserRole::tryFrom($state)?->color() ?? 'gray')
                    ->sortable(),
                TextColumn::make('account_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, User $record): string => AccountStatus::tryFrom($state ?? $record->accountStatusValue())?->label() ?? ucfirst((string) $state))
                    ->color(fn (?string $state, User $record): string => AccountStatus::tryFrom($state ?? $record->accountStatusValue())?->color() ?? 'gray'),
                TextColumn::make('posts_count')
                    ->label('Posts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('comments_count')
                    ->label('Comments')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('followers_count')
                    ->label('Followers')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('following_count')
                    ->label('Following')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(UserRole::options()),
                SelectFilter::make('account_status')
                    ->options(AccountStatus::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('restrict')
                    ->label('Restrict')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->visible(fn (User $record): bool => PanelAccess::isAdmin() && $record->isActive())
                    ->schema([
                        Textarea::make('reason')
                            ->label('Restriction reason')
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

                        Notification::make()
                            ->title('User restricted successfully.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Action::make('ban')
                    ->label('Ban')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (User $record): bool => PanelAccess::isAdmin() && ! $record->isBanned())
                    ->schema([
                        Textarea::make('reason')
                            ->label('Ban reason')
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

                        Notification::make()
                            ->title('User banned successfully.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (User $record): bool => PanelAccess::isAdmin() && ! $record->isActive())
                    ->action(function (User $record): void {
                        app(AdminModerationService::class)->updateAccountStatus(
                            $record,
                            AccountStatus::Active->value,
                            PanelAccess::user(),
                        );

                        Notification::make()
                            ->title('User reactivated successfully.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }
}
