<?php

namespace App\Filament\Resources\ModerationLogs\Schemas;

use App\Filament\Resources\ModerationLogs\ModerationLogResource;
use App\Models\ModerationLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ModerationLogInfolist
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
                                TextEntry::make('created_at')
                                    ->label(__('admin.ui.created'))
                                    ->dateTime(),
                                TextEntry::make('actor.name')
                                    ->label(__('admin.ui.actor'))
                                    ->placeholder(__('admin.ui.system')),
                                TextEntry::make('targetUser.name')
                                    ->label(__('admin.ui.target_user'))
                                    ->placeholder(__('admin.ui.no_user_target')),
                                TextEntry::make('action')
                                    ->label(__('admin.ui.action'))
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => ModerationLogResource::actionLabel($state)),
                                TextEntry::make('subject_type')
                                    ->label(__('admin.ui.subject_type'))
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => ModerationLogResource::subjectTypeLabel($state))
                                    ->color('gray'),
                                TextEntry::make('subject_id')
                                    ->label(__('admin.ui.subject_id')),
                                TextEntry::make('report_id')
                                    ->label(__('admin.ui.report_id'))
                                    ->placeholder(__('admin.ui.no_linked_report')),
                                TextEntry::make('subject_summary')
                                    ->label(__('admin.ui.subject'))
                                    ->state(fn (ModerationLog $record): string => ModerationLogResource::subjectSummary($record))
                                    ->columnSpanFull(),
                                TextEntry::make('reason')
                                    ->placeholder(__('admin.ui.no_moderation_reason_recorded'))
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make(__('admin.ui.metadata'))
                    ->schema([
                        TextEntry::make('metadata')
                            ->state(fn (ModerationLog $record): string => json_encode($record->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                            ->default('{}')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
