<?php

namespace App\Filament\Resources\UserNotifications\Tables;

use App\Enums\NotificationType;
use App\Enums\UserRole;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('recipient.name')
                    ->label('Recipient')
                    ->searchable(),
                TextColumn::make('actor.name')
                    ->label('Actor')
                    ->placeholder('System')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => NotificationType::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => NotificationType::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('title')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('body')
                    ->limit(70)
                    ->toggleable(),
                IconColumn::make('is_read')
                    ->label('Read')
                    ->boolean(),
                TextColumn::make('read_at')
                    ->label('Read at')
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(NotificationType::options()),
                TernaryFilter::make('is_read')
                    ->label('Read'),
                SelectFilter::make('recipient_role')
                    ->label('Recipient role')
                    ->options(UserRole::options())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $builder): Builder => $builder->whereHas(
                            'recipient',
                            fn (Builder $recipientQuery): Builder => $recipientQuery->where('role', $data['value'])
                        )
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
