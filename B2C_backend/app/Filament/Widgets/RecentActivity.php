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
    protected static ?string $heading = 'Recent Moderation Activity';

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ModerationLog::query()->with(['actor', 'subject'])->latest())
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('d M Y, H:i')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (ModerationLog $record): string => $record->created_at->format('d M Y, H:i:s')),
                TextColumn::make('actor.name')
                    ->label('By')
                    ->default('System')
                    ->searchable()
                    ->icon('heroicon-m-user-circle'),
                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('On')
                    ->formatStateUsing(fn (string $state): string => ucfirst(class_basename($state)))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('reason')
                    ->label('Note')
                    ->limit(80)
                    ->default('—')
                    ->wrap(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (ModerationLog $record): string => ModerationLogResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25])
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateHeading('No moderation activity yet.')
            ->emptyStateDescription('Staff actions will appear here as they happen.');
    }
}
