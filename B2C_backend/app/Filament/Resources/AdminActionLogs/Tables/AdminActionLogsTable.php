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
                    ->label('Actor')
                    ->placeholder('System')
                    ->searchable(),
                TextColumn::make('targetUser.name')
                    ->label('Target user')
                    ->placeholder('No target user')
                    ->searchable(),
                TextColumn::make('action')
                    ->badge()
                    ->searchable(),
                TextColumn::make('description')
                    ->limit(70),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('actor_user_id')
                    ->relationship('actor', 'name')
                    ->label('Actor')
                    ->searchable()
                    ->preload(),
                Filter::make('action')
                    ->schema([
                        TextInput::make('action')
                            ->label('Action'),
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
