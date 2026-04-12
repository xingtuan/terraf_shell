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
                Section::make('Overview')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('id'),
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime(),
                                TextEntry::make('actor.name')
                                    ->label('Actor')
                                    ->placeholder('System'),
                                TextEntry::make('action')
                                    ->badge(),
                                TextEntry::make('subject_type')
                                    ->label('Subject type')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => ModerationLogResource::subjectTypeLabel($state))
                                    ->color('gray'),
                                TextEntry::make('subject_id')
                                    ->label('Subject ID'),
                                TextEntry::make('subject_summary')
                                    ->label('Subject')
                                    ->state(fn (ModerationLog $record): string => ModerationLogResource::subjectSummary($record))
                                    ->columnSpanFull(),
                                TextEntry::make('reason')
                                    ->placeholder('No moderation reason recorded.')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('metadata')
                            ->state(fn (ModerationLog $record): string => json_encode($record->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                            ->default('{}')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
