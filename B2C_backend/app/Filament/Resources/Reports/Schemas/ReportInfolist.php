<?php

namespace App\Filament\Resources\Reports\Schemas;

use App\Enums\ReportStatus;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
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
                Section::make('Overview')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('id'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ReportStatus::tryFrom($state)?->label() ?? ucfirst($state))
                                    ->color(fn (string $state): string => ReportStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('reporter.name')
                                    ->label('Reporter'),
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime(),
                                TextEntry::make('target_type')
                                    ->label('Target type')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ReportResource::targetTypeLabel($state))
                                    ->color('gray'),
                                TextEntry::make('target_id')
                                    ->label('Target ID'),
                                TextEntry::make('target_summary')
                                    ->label('Target content')
                                    ->state(fn (Report $record): string => ReportResource::targetSummary($record))
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Reason')
                    ->schema([
                        TextEntry::make('reason'),
                        TextEntry::make('description')
                            ->label('Reporter description')
                            ->placeholder('No additional detail provided.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Review')
                    ->schema([
                        TextEntry::make('moderator_note')
                            ->placeholder('No moderator note recorded.')
                            ->columnSpanFull(),
                        TextEntry::make('reviewer.name')
                            ->label('Reviewed by')
                            ->placeholder('Not reviewed yet.'),
                        TextEntry::make('reviewed_at')
                            ->label('Reviewed at')
                            ->dateTime()
                            ->placeholder('Not reviewed yet.'),
                    ]),
            ]);
    }
}
