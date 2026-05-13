<?php

namespace App\Filament\Resources\AdminActionLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdminActionLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('actor.name')
                    ->label(__('admin.ui.actor'))
                    ->placeholder(__('admin.ui.system'))
                    ->searchable(),
                TextColumn::make('targetUser.name')
                    ->label(__('admin.ui.target_user'))
                    ->placeholder(__('admin.ui.no_target_user_2'))
                    ->searchable(),
                TextColumn::make('action')
                    ->label(__('admin.ui.action'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('description')
                    ->label(__('admin.ui.description'))
                    ->limit(70),
                TextColumn::make('created_at')
                    ->label(__('admin.ui.created'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('actor_user_id')
                    ->relationship('actor', 'name')
                    ->label(__('admin.ui.actor'))
                    ->searchable()
                    ->preload(),
                Filter::make('action')
                    ->schema([
                        TextInput::make('action')
                            ->label(__('admin.ui.action')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['action'] ?? null),
                        fn (Builder $builder): Builder => $builder->where('action', 'like', '%'.trim((string) $data['action']).'%')
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
