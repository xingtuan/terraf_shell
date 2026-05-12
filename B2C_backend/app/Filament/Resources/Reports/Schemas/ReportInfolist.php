<?php

namespace App\Filament\Resources\Reports\Schemas;

use App\Enums\ReportStatus;
use App\Filament\Resources\ModerationLogs\ModerationLogResource;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.overview'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('id'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ReportStatus::tryFrom($state)?->label() ?? ucfirst($state))
                                    ->color(fn (string $state): string => ReportStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('reporter.name')
                                    ->label(__('admin.ui.reporter')),
                                TextEntry::make('created_at')
                                    ->label(__('admin.ui.created'))
                                    ->dateTime(),
                                TextEntry::make('target_type')
                                    ->label(__('admin.ui.target_type'))
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ReportResource::targetTypeLabel($state))
                                    ->color('gray'),
                                TextEntry::make('target_id')
                                    ->label(__('admin.ui.target_id')),
                                TextEntry::make('target_summary')
                                    ->label(__('admin.ui.target_content'))
                                    ->state(fn (Report $record): string => ReportResource::targetSummary($record))
                                    ->columnSpanFull(),
                                TextEntry::make('violations_count')
                                    ->label(__('admin.ui.violations'))
                                    ->state(fn (Report $record): int => (int) ($record->violations_count ?? $record->violations()->count())),
                            ]),
                    ]),
                Section::make(__('admin.ui.reason'))
                    ->schema([
                        TextEntry::make('reason'),
                        TextEntry::make('description')
                            ->label(__('admin.ui.reporter_description'))
                            ->placeholder(__('admin.ui.no_additional_detail_provided'))
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.review'))
                    ->schema([
                        TextEntry::make('moderator_note')
                            ->placeholder(__('admin.ui.no_moderator_note_recorded'))
                            ->columnSpanFull(),
                        TextEntry::make('reviewer.name')
                            ->label(__('admin.ui.reviewed_by'))
                            ->placeholder(__('admin.ui.not_reviewed_yet')),
                        TextEntry::make('reviewed_at')
                            ->label(__('admin.ui.reviewed_at'))
                            ->dateTime()
                            ->placeholder(__('admin.ui.not_reviewed_yet')),
                    ]),
                Section::make(__('admin.ui.governance'))
                    ->schema([
                        RepeatableEntry::make('violations')
                            ->label(__('admin.ui.violation_records'))
                            ->schema([
                                TextEntry::make('type')
                                    ->badge(),
                                TextEntry::make('severity')
                                    ->badge(),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('reason')
                                    ->placeholder(__('admin.ui.no_reason_recorded'))
                                    ->columnSpanFull(),
                            ]),
                        RepeatableEntry::make('moderationLogs')
                            ->label(__('admin.ui.moderation_history_2'))
                            ->schema([
                                TextEntry::make('action')
                                    ->label(__('admin.ui.action'))
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => ModerationLogResource::actionLabel($state)),
                                TextEntry::make('actor.name')
                                    ->label(__('admin.ui.actor'))
                                    ->placeholder(__('admin.ui.system')),
                                TextEntry::make('reason')
                                    ->placeholder(__('admin.ui.no_note_provided'))
                                    ->columnSpanFull(),
                                TextEntry::make('created_at')
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
