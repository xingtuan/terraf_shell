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
                Section::make(__('admin.ui.report'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('reporter')
                                    ->label(__('admin.ui.reporter'))
                                    ->content(fn (?Report $record): string => $record?->reporter?->name ? $record->reporter->name.' (@'.$record->reporter->username.')' : __('admin.ui.unknown_reporter')),
                                Placeholder::make('status')
                                    ->label(__('admin.ui.status'))
                                    ->content(fn (?Report $record): string => $record ? (ReportStatus::tryFrom($record->status)?->label() ?? ucfirst($record->status)) : '-'),
                                Placeholder::make('target_type')
                                    ->label(__('admin.ui.target_type'))
                                    ->content(fn (?Report $record): string => $record ? ReportResource::targetTypeLabel($record->target_type) : '-'),
                                Placeholder::make('target_id')
                                    ->label(__('admin.ui.target_id'))
                                    ->content(fn (?Report $record): string => $record ? (string) $record->target_id : '-'),
                                Placeholder::make('target_summary')
                                    ->label(__('admin.ui.target_content'))
                                    ->content(fn (?Report $record): string => $record ? ReportResource::targetSummary($record) : '-')
                                    ->columnSpanFull(),
                                Placeholder::make('target_owner')
                                    ->label(__('admin.ui.target_owner'))
                                    ->content(fn (?Report $record): string => $record ? ReportResource::targetOwnerSummary($record) : '-')
                                    ->columnSpanFull(),
                                Placeholder::make('reason')
                                    ->label(__('admin.ui.reason'))
                                    ->content(fn (?Report $record): string => $record?->reason ?? '-'),
                                Placeholder::make('description')
                                    ->label(__('admin.ui.reporter_description'))
                                    ->content(fn (?Report $record): string => $record?->description ?: __('admin.ui.no_additional_detail_provided'))
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make(__('admin.ui.review'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Textarea::make('moderator_note')
                                    ->label(__('admin.ui.internal_moderator_note'))
                                    ->rows(5)
                                    ->columnSpanFull(),
                                Textarea::make('public_note')
                                    ->label(__('admin.ui.public_note'))
                                    ->rows(5)
                                    ->columnSpanFull(),
                                Placeholder::make('resolution_action')
                                    ->label(__('admin.ui.resolution_action'))
                                    ->content(fn (?Report $record): string => $record?->resolution_action ? __('admin.report_resolution_action.'.$record->resolution_action) : __('admin.ui.none')),
                                Placeholder::make('reviewed_by')
                                    ->label(__('admin.ui.reviewed_by'))
                                    ->content(fn (?Report $record): string => $record?->reviewer?->name ?? __('admin.ui.not_reviewed_yet')),
                                Placeholder::make('reviewed_at')
                                    ->label(__('admin.ui.reviewed_at'))
                                    ->content(fn (?Report $record): string => $record?->reviewed_at?->toDateTimeString() ?? __('admin.ui.not_reviewed_yet')),
                                Placeholder::make('resolved_at')
                                    ->label(__('admin.ui.resolved_at'))
                                    ->content(fn (?Report $record): string => $record?->resolved_at?->toDateTimeString() ?? __('admin.ui.not_resolved')),
                                Placeholder::make('dismissed_at')
                                    ->label(__('admin.ui.dismissed_at'))
                                    ->content(fn (?Report $record): string => $record?->dismissed_at?->toDateTimeString() ?? __('admin.ui.none')),
                                Placeholder::make('completed_at')
                                    ->label(__('admin.ui.completed_at'))
                                    ->content(fn (?Report $record): string => $record?->completed_at?->toDateTimeString() ?? __('admin.ui.none')),
                            ]),
                    ]),
            ]);
    }
}
