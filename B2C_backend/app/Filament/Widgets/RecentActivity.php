<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ModerationLogs\ModerationLogResource;
use App\Models\ModerationLog;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentActivity extends TableWidget
{
    protected static ?string $heading = 'Recent Activity';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ModerationLog::query()->with(['actor', 'subject'])->latest())
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->since()
                    ->sortable(),
                TextColumn::make('actor.name')
                    ->label('Actor')
                    ->default('System')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Target type')
                    ->formatStateUsing(fn (string $state): string => ucfirst(class_basename($state)))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('subject_id')
                    ->label('Target ID')
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(70)
                    ->default('No note provided.'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (ModerationLog $record): string => ModerationLogResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([10]);
    }
}
