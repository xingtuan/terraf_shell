<?php

namespace App\Filament\Resources\UserViolations\Schemas;

use App\Enums\UserViolationSeverity;
use App\Enums\UserViolationStatus;
use App\Enums\UserViolationType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserViolationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Violation')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('User'),
                                TextEntry::make('actor.name')
                                    ->label('Recorded by')
                                    ->placeholder('System'),
                                TextEntry::make('type')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => UserViolationType::tryFrom($state)?->label() ?? $state)
                                    ->color(fn (string $state): string => UserViolationType::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('severity')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => UserViolationSeverity::tryFrom($state)?->label() ?? $state)
                                    ->color(fn (string $state): string => UserViolationSeverity::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => UserViolationStatus::tryFrom($state)?->label() ?? $state)
                                    ->color(fn (string $state): string => UserViolationStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('report.id')
                                    ->label('Report')
                                    ->placeholder('No linked report.'),
                                TextEntry::make('subject_type')
                                    ->label('Subject type')
                                    ->placeholder('No linked subject.'),
                                TextEntry::make('subject_id')
                                    ->label('Subject ID')
                                    ->placeholder('No linked subject.'),
                                TextEntry::make('reason')
                                    ->placeholder('No reason recorded.')
                                    ->columnSpanFull(),
                                TextEntry::make('resolution_note')
                                    ->placeholder('No resolution note.')
                                    ->columnSpanFull(),
                                TextEntry::make('occurred_at')
                                    ->label('Occurred')
                                    ->dateTime(),
                                TextEntry::make('resolved_at')
                                    ->label('Resolved')
                                    ->dateTime()
                                    ->placeholder('Open violation.'),
                                TextEntry::make('resolver.name')
                                    ->label('Resolved by')
                                    ->placeholder('Not resolved.'),
                            ]),
                    ]),
            ]);
    }
}
