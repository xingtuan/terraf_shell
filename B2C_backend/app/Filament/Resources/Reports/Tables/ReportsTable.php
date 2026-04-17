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
                    ->label('Reporter')
                    ->description(fn (Report $record): string => '@'.$record->reporter->username)
                    ->searchable(['name', 'username']),
                TextColumn::make('target_type')
                    ->label('Target type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ReportResource::targetTypeLabel($state))
                    ->color('gray'),
                TextColumn::make('target_id')
                    ->label('Target ID')
                    ->sortable(),
                TextColumn::make('target_summary')
                    ->label('Target')
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
                    ->label('Reviewed by')
                    ->placeholder('Unassigned')
                    ->toggleable(),
                TextColumn::make('violations_count')
                    ->label('Violations')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
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
                    ->label('Reviewed by')
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Created from'),
                        DatePicker::make('created_until')
                            ->label('Created until'),
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
                    ->label('View target')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Report $record): ?string => ReportResource::targetAdminUrl($record))
                    ->visible(fn (Report $record): bool => filled(ReportResource::targetAdminUrl($record))),
                self::statusAction('open', 'Mark as Open', ReportStatus::Pending->value, 'warning'),
                self::statusAction('review', 'Mark as Reviewed', ReportStatus::Reviewed->value, 'info'),
                self::statusAction('resolve', 'Mark as Resolved', ReportStatus::Resolved->value, 'success'),
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
                    ->label('Moderator note')
                    ->rows(4),
                Textarea::make('reason')
                    ->label('Audit note')
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
                    ->title($label.' successfully.')
                    ->success()
                    ->send();
            });
    }
}
