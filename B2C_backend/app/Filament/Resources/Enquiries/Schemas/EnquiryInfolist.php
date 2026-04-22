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
                Section::make('Enquiry')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('id'),
                                TextEntry::make('reference'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->label() ?? $state)
                                    ->color(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('inquiry_type')
                                    ->label('Enquiry type')
                                    ->placeholder('General enquiry'),
                                TextEntry::make('subject')
                                    ->state(fn (Inquiry $record): string => $record->subject),
                                TextEntry::make('source_page')
                                    ->label('Source')
                                    ->placeholder('Not tracked.'),
                                TextEntry::make('name'),
                                TextEntry::make('email')
                                    ->label('Email'),
                                TextEntry::make('company_name')
                                    ->label('Company / Organization'),
                                TextEntry::make('organization_type')
                                    ->placeholder('Not specified.'),
                                TextEntry::make('phone')
                                    ->placeholder('Not provided.'),
                                TextEntry::make('country')
                                    ->placeholder('Not provided.'),
                                TextEntry::make('region')
                                    ->placeholder('Not provided.'),
                                TextEntry::make('message')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Admin Review')
                    ->schema([
                        TextEntry::make('internal_notes')
                            ->label('Internal notes')
                            ->placeholder('No internal notes yet.')
                            ->columnSpanFull(),
                        TextEntry::make('assignee.name')
                            ->label('Owner')
                            ->placeholder('Unassigned.'),
                        TextEntry::make('reviewer.name')
                            ->label('Reviewed by')
                            ->placeholder('Not reviewed yet.'),
                        TextEntry::make('reviewed_at')
                            ->label('Reviewed at')
                            ->dateTime()
                            ->placeholder('Not reviewed yet.'),
                        TextEntry::make('created_at')
                            ->label('Submitted at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Updated at')
                            ->dateTime(),
                    ]),
                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('metadata_summary')
                            ->label('Captured metadata')
                            ->state(function (Inquiry $record): string {
                                if (($record->metadata ?? []) === []) {
                                    return 'No additional metadata captured.';
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
