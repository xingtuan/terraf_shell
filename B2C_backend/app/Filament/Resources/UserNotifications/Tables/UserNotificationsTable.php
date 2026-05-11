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
                    ->label(__('admin.ui.recipient'))
                    ->searchable(),
                TextColumn::make('actor.name')
                    ->label(__('admin.ui.actor'))
                    ->placeholder(__('admin.ui.system'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('admin.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => NotificationType::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => NotificationType::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('title')
                    ->label(__('admin.ui.title'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('body')
                    ->label(__('admin.ui.body'))
                    ->limit(70)
                    ->toggleable(),
                IconColumn::make('is_read')
                    ->label(__('admin.ui.read'))
                    ->boolean(),
                TextColumn::make('read_at')
                    ->label(__('admin.ui.read_at'))
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('admin.ui.created'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('admin.fields.type'))
                    ->options(NotificationType::options()),
                TernaryFilter::make('is_read')
                    ->label(__('admin.ui.read')),
                SelectFilter::make('recipient_role')
                    ->label(__('admin.ui.recipient_role'))
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
