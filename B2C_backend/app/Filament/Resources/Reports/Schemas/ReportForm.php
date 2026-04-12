<?php

namespace App\Filament\Resources\Reports\Schemas;

use App\Enums\ReportStatus;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Report')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('reporter')
                                    ->label('Reporter')
                                    ->content(fn (?Report $record): string => $record?->reporter?->name ? $record->reporter->name.' (@'.$record->reporter->username.')' : 'Unknown reporter'),
                                Placeholder::make('status')
                                    ->label('Status')
                                    ->content(fn (?Report $record): string => $record ? (ReportStatus::tryFrom($record->status)?->label() ?? ucfirst($record->status)) : '-'),
                                Placeholder::make('target_type')
                                    ->label('Target type')
                                    ->content(fn (?Report $record): string => $record ? ReportResource::targetTypeLabel($record->target_type) : '-'),
                                Placeholder::make('target_id')
                                    ->label('Target ID')
                                    ->content(fn (?Report $record): string => $record ? (string) $record->target_id : '-'),
                                Placeholder::make('target_summary')
                                    ->label('Target content')
                                    ->content(fn (?Report $record): string => $record ? ReportResource::targetSummary($record) : '-')
                                    ->columnSpanFull(),
                                Placeholder::make('reason')
                                    ->content(fn (?Report $record): string => $record?->reason ?? '-'),
                                Placeholder::make('description')
                                    ->label('Reporter description')
                                    ->content(fn (?Report $record): string => $record?->description ?: 'No additional detail provided.')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Review')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Textarea::make('moderator_note')
                                    ->label('Moderator note')
                                    ->rows(5)
                                    ->columnSpanFull(),
                                Placeholder::make('reviewed_by')
                                    ->label('Reviewed by')
                                    ->content(fn (?Report $record): string => $record?->reviewer?->name ?? 'Not reviewed yet.'),
                                Placeholder::make('reviewed_at')
                                    ->label('Reviewed at')
                                    ->content(fn (?Report $record): string => $record?->reviewed_at?->toDateTimeString() ?? 'Not reviewed yet.'),
                            ]),
                    ]),
            ]);
    }
}
