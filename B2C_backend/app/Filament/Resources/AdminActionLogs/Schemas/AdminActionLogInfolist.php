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
                Section::make(__('admin.ui.action'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('actor.name')
                                    ->label(__('admin.ui.actor'))
                                    ->placeholder(__('admin.ui.system')),
                                TextEntry::make('targetUser.name')
                                    ->label(__('admin.ui.target_user'))
                                    ->placeholder(__('admin.ui.no_target_user')),
                                TextEntry::make('action')
                                    ->label(__('admin.ui.action'))
                                    ->badge(),
                                TextEntry::make('created_at')
                                    ->label(__('admin.ui.created'))
                                    ->dateTime(),
                                TextEntry::make('subject_type')
                                    ->label(__('admin.ui.subject_type'))
                                    ->placeholder(__('admin.ui.no_subject')),
                                TextEntry::make('subject_id')
                                    ->label(__('admin.ui.subject_id'))
                                    ->placeholder(__('admin.ui.no_subject')),
                                TextEntry::make('description')
                                    ->label(__('admin.ui.description'))
                                    ->placeholder(__('admin.ui.no_description_provided'))
                                    ->columnSpanFull(),
                                TextEntry::make('metadata')
                                    ->label(__('admin.ui.metadata'))
                                    ->state(fn ($record): string => json_encode($record->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
