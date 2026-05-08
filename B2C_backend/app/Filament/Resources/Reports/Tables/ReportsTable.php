<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Enums\ReportStatus;
use App\Filament\Resources\Reports\ReportResource;
use App\Filament\Support\PanelAccess;
use App\Models\Report;
use App\Services\AdminModerationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('reporter.name')
                    ->label(__('admin.ui.reporter'))
                    ->description(fn (Report $record): string => '@'.$record->reporter->username)
                    ->searchable(['name', 'username']),
                TextColumn::make('target_type')
                    ->label(__('admin.ui.target_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ReportResource::targetTypeLabel($state))
                    ->color('gray'),
                TextColumn::make('target_id')
                    ->label(__('admin.ui.target_id'))
                    ->sortable(),
                TextColumn::make('target_summary')
                    ->label(__('admin.ui.target'))
                    ->state(fn (Report $record): string => ReportResource::targetSummary($record))
                    ->limit(50),
                TextColumn::make('reason')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ReportStatus::tryFrom($state)?->label() ?? ucfirst($state))
                    ->color(fn (string $state): string => ReportStatus::tryFrom($state)?->color() ?? 'gray')
                    ->sortable(),
                TextColumn::make('reviewer.name')
                    ->label(__('admin.ui.reviewed_by'))
                    ->placeholder(__('admin.ui.unassigned'))
                    ->toggleable(),
                TextColumn::make('violations_count')
                    ->label(__('admin.ui.violations'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('admin.ui.created'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ReportStatus::options()),
                SelectFilter::make('target_type')
                    ->options(ReportResource::targetTypeOptions()),
                SelectFilter::make('reviewed_by')
                    ->relationship('reviewer', 'name')
                    ->label(__('admin.ui.reviewed_by'))
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('admin.ui.created_from')),
                        DatePicker::make('created_until')
                            ->label(__('admin.ui.created_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $builder, string $date): Builder => $builder->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $builder, string $date): Builder => $builder->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('viewTarget')
                    ->label(__('admin.ui.view_target'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Report $record): ?string => ReportResource::targetAdminUrl($record))
                    ->visible(fn (Report $record): bool => filled(ReportResource::targetAdminUrl($record))),
                self::statusAction('open', __('admin.ui.mark_as_open'), ReportStatus::Pending->value, 'warning'),
                self::statusAction('review', __('admin.ui.mark_as_reviewed'), ReportStatus::Reviewed->value, 'info'),
                self::statusAction('resolve', __('admin.actions.mark_resolved'), ReportStatus::Resolved->value, 'success'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }

    private static function statusAction(string $name, string $label, string $status, string $color): Action
    {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->visible(fn (Report $record): bool => PanelAccess::isStaff() && $record->status !== $status)
            ->schema([
                Textarea::make('moderator_note')
                    ->label(__('admin.ui.moderator_note'))
                    ->rows(4),
                Textarea::make('reason')
                    ->label(__('admin.ui.audit_note'))
                    ->rows(3),
            ])
            ->requiresConfirmation()
            ->action(function (Report $record, array $data) use ($status, $label): void {
                app(AdminModerationService::class)->updateReportStatus(
                    $record,
                    $status,
                    PanelAccess::user(),
                    $data['reason'] ?? null,
                );

                if (filled($data['moderator_note'] ?? null)) {
                    $record->forceFill([
                        'moderator_note' => $data['moderator_note'],
                    ])->save();
                }

                Notification::make()
                    ->title(__('admin.ui.status_action_completed', ['label' => $label]))
                    ->success()
                    ->send();
            });
    }
}
