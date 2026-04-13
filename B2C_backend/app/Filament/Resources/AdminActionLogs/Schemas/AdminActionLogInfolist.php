<?php

namespace App\Filament\Resources\AdminActionLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminActionLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Action')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('actor.name')
                                    ->label('Actor')
                                    ->placeholder('System'),
                                TextEntry::make('targetUser.name')
                                    ->label('Target user')
                                    ->placeholder('No target user.'),
                                TextEntry::make('action')
                                    ->badge(),
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime(),
                                TextEntry::make('subject_type')
                                    ->label('Subject type')
                                    ->placeholder('No subject.'),
                                TextEntry::make('subject_id')
                                    ->label('Subject ID')
                                    ->placeholder('No subject.'),
                                TextEntry::make('description')
                                    ->placeholder('No description provided.')
                                    ->columnSpanFull(),
                                TextEntry::make('metadata')
                                    ->state(fn ($record): string => json_encode($record->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
