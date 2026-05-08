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
                Section::make(__('admin.ui.violation'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label(__('admin.ui.user')),
                                TextEntry::make('actor.name')
                                    ->label(__('admin.ui.recorded_by'))
                                    ->placeholder(__('admin.ui.system')),
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
                                    ->label(__('admin.ui.report'))
                                    ->placeholder(__('admin.ui.no_linked_report')),
                                TextEntry::make('subject_type')
                                    ->label(__('admin.ui.subject_type'))
                                    ->placeholder(__('admin.ui.no_linked_subject')),
                                TextEntry::make('subject_id')
                                    ->label(__('admin.ui.subject_id'))
                                    ->placeholder(__('admin.ui.no_linked_subject')),
                                TextEntry::make('reason')
                                    ->placeholder(__('admin.ui.no_reason_recorded'))
                                    ->columnSpanFull(),
                                TextEntry::make('resolution_note')
                                    ->placeholder(__('admin.ui.no_resolution_note'))
                                    ->columnSpanFull(),
                                TextEntry::make('occurred_at')
                                    ->label(__('admin.ui.occurred'))
                                    ->dateTime(),
                                TextEntry::make('resolved_at')
                                    ->label(__('admin.ui.resolved'))
                                    ->dateTime()
                                    ->placeholder(__('admin.ui.open_violation')),
                                TextEntry::make('resolver.name')
                                    ->label(__('admin.ui.resolved_by'))
                                    ->placeholder(__('admin.ui.not_resolved')),
                            ]),
                    ]),
            ]);
    }
}
