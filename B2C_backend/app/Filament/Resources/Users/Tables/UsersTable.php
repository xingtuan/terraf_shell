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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->state(fn (User $record): bool => $record->email_verified_at !== null)
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('email_verified_at', $direction)),
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
                    ->label('Ideas')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('comments_count')
                    ->label('Comments')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('profile.school_or_company')
                    ->label('School / Company')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('profile.region')
                    ->label('Region')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('profile.open_to_collab')
                    ->label('Open to Collab')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('received_moderation_logs_count')
                    ->label('Moderation')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('violations_count')
                    ->label('Violations')
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
                TernaryFilter::make('email_verified_at')
                    ->label('Email verified')
                    ->nullable(),
                Filter::make('open_to_collab')
                    ->label('Open to collaborate')
                    ->query(fn (Builder $query): Builder => $query->whereHas(
                        'profile',
                        fn (Builder $profileQuery): Builder => $profileQuery->where('open_to_collab', true)
                    )),
                Filter::make('organization')
                    ->label('School / Company')
                    ->schema([
                        TextInput::make('school_or_company')
                            ->label('School / company'),
                        TextInput::make('region')
                            ->label('Region'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->whereHas('profile', function (Builder $profileQuery) use ($data): void {
                            $profileQuery
                                ->when(
                                    filled($data['school_or_company'] ?? null),
                                    fn (Builder $builder): Builder => $builder->where(
                                        'school_or_company',
                                        'like',
                                        '%'.trim((string) $data['school_or_company']).'%'
                                    )
                                )
                                ->when(
                                    filled($data['region'] ?? null),
                                    fn (Builder $builder): Builder => $builder->where(
                                        'region',
                                        'like',
                                        '%'.trim((string) $data['region']).'%'
                                    )
                                );
                        });
                    }),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Created from'),
                        DatePicker::make('created_until')
                            ->label('Created until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => PanelAccess::isAdmin()),
                Action::make('restrict')
                    ->label('Restrict')
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
                Action::make('resendVerification')
                    ->label('Resend Verification')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->visible(fn (User $record): bool => PanelAccess::isAdmin() && $record->email_verified_at === null)
                    ->action(function (User $record): void {
                        $record->sendEmailVerificationNotification();

                        Notification::make()
                            ->title('Verification email sent.')
                            ->success()
                            ->send();
                    }),
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
