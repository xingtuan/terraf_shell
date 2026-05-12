<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ModerationLogs\ModerationLogResource;
use App\Models\ModerationLog;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class RecentActivity extends TableWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 9;

    protected function getTableHeading(): string|Htmlable|null
    {
        return __('admin.widgets.recent_activity');
    }

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ModerationLog::query()->with(['actor', 'subject'])->latest())
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('admin.ui.when'))
                    ->dateTime('d M Y, H:i')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (ModerationLog $record): string => $record->created_at->format('d M Y, H:i:s')),
                TextColumn::make('actor.name')
                    ->label(__('admin.ui.by'))
                    ->default(__('admin.ui.system'))
                    ->searchable()
                    ->icon('heroicon-m-user-circle'),
                TextColumn::make('action')
                    ->label(__('admin.ui.action'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ModerationLogResource::actionLabel($state))
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label(__('admin.ui.on'))
                    ->formatStateUsing(fn (string $state): string => ModerationLogResource::subjectTypeLabel($state))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('reason')
                    ->label(__('admin.ui.note'))
                    ->formatStateUsing(fn (?string $state): string => ModerationLogResource::reasonLabel($state) ?? __('admin.ui.no_note_provided'))
                    ->limit(80)
                    ->wrap(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (ModerationLog $record): string => ModerationLogResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25])
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateHeading(__('admin.ui.no_moderation_activity_yet'))
            ->emptyStateDescription(__('admin.ui.staff_actions_will_appear_here_as_they_happen'));
    }
}
