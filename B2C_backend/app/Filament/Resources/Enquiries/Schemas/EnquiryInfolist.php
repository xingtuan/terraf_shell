<?php

namespace App\Filament\Resources\Enquiries\Schemas;

use App\Enums\B2BLeadStatus;
use App\Models\Inquiry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EnquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.enquiry'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('id'),
                                TextEntry::make('reference'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->label() ?? $state)
                                    ->color(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('priority')
                                    ->label(__('admin.fields.priority'))
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => $state ? __("admin.leads.priority.{$state}") : __('admin.leads.priority.normal')),
                                TextEntry::make('inquiry_type')
                                    ->label(__('admin.ui.enquiry_type'))
                                    ->placeholder(__('admin.ui.general_enquiry')),
                                TextEntry::make('subject')
                                    ->state(fn (Inquiry $record): string => $record->subject),
                                TextEntry::make('source_page')
                                    ->label(__('admin.ui.source'))
                                    ->placeholder(__('admin.ui.not_tracked')),
                                TextEntry::make('name'),
                                TextEntry::make('email')
                                    ->label(__('admin.ui.email')),
                                TextEntry::make('company_name')
                                    ->label(__('admin.ui.company_organization')),
                                TextEntry::make('organization_type')
                                    ->placeholder(__('admin.ui.not_specified')),
                                TextEntry::make('phone')
                                    ->placeholder(__('admin.ui.not_provided')),
                                TextEntry::make('country')
                                    ->placeholder(__('admin.ui.not_provided')),
                                TextEntry::make('region')
                                    ->placeholder(__('admin.ui.not_provided')),
                                TextEntry::make('message')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make(__('admin.ui.admin_review'))
                    ->schema([
                        TextEntry::make('internal_notes')
                            ->label(__('admin.ui.internal_notes'))
                            ->placeholder(__('admin.ui.no_internal_notes_yet'))
                            ->columnSpanFull(),
                        TextEntry::make('assignee.name')
                            ->label(__('admin.ui.owner'))
                            ->placeholder(__('admin.ui.unassigned_2')),
                        TextEntry::make('follow_up_at')
                            ->label(__('admin.fields.follow_up_at'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('reviewer.name')
                            ->label(__('admin.ui.reviewed_by'))
                            ->placeholder(__('admin.ui.not_reviewed_yet')),
                        TextEntry::make('reviewed_at')
                            ->label(__('admin.ui.reviewed_at'))
                            ->dateTime()
                            ->placeholder(__('admin.ui.not_reviewed_yet')),
                        TextEntry::make('created_at')
                            ->label(__('admin.ui.submitted_at'))
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('admin.ui.updated_at'))
                            ->dateTime(),
                    ]),
                Section::make(__('admin.ui.metadata'))
                    ->schema([
                        TextEntry::make('metadata_summary')
                            ->label(__('admin.ui.captured_metadata'))
                            ->state(function (Inquiry $record): string {
                                if (($record->metadata ?? []) === []) {
                                    return __('admin.ui.no_additional_metadata_captured');
                                }

                                return collect($record->metadata)
                                    ->map(fn (mixed $value, string $key): string => $key.': '.(is_scalar($value) || $value === null ? (string) ($value ?? 'null') : json_encode($value)))
                                    ->implode("\n");
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
