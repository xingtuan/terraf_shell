<?php

namespace App\Filament\Resources\Enquiries\Schemas;

use App\Enums\B2BLeadStatus;
use App\Enums\UserRole;
use App\Models\Inquiry;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EnquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Enquiry Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('reference')
                                    ->content(fn (?Inquiry $record): string => $record?->reference ?? 'Generated automatically'),
                                Placeholder::make('status_badge')
                                    ->label('Current status')
                                    ->content(fn (?Inquiry $record): string => $record ? (B2BLeadStatus::tryFrom($record->status)?->label() ?? $record->status) : '-'),
                                Placeholder::make('name')
                                    ->content(fn (?Inquiry $record): string => $record?->name ?? '-'),
                                Placeholder::make('email')
                                    ->label('Email')
                                    ->content(fn (?Inquiry $record): string => $record?->email ?? '-'),
                                Placeholder::make('company_name')
                                    ->label('Company / Organization')
                                    ->content(fn (?Inquiry $record): string => $record?->company_name ?? '-'),
                                Placeholder::make('organization_type')
                                    ->content(fn (?Inquiry $record): string => $record?->organization_type ?: 'Not specified.'),
                                Placeholder::make('country')
                                    ->content(fn (?Inquiry $record): string => $record?->country ?: 'Not specified.'),
                                Placeholder::make('region')
                                    ->content(fn (?Inquiry $record): string => $record?->region ?: 'Not specified.'),
                                Placeholder::make('phone')
                                    ->content(fn (?Inquiry $record): string => $record?->phone ?: 'No phone provided.'),
                                Placeholder::make('source_page')
                                    ->label('Source')
                                    ->content(fn (?Inquiry $record): string => $record?->source_page ?: 'No source tracked.'),
                                Placeholder::make('inquiry_type')
                                    ->label('Enquiry type')
                                    ->content(fn (?Inquiry $record): string => $record?->inquiry_type ?: 'General enquiry'),
                                Placeholder::make('subject')
                                    ->content(fn (?Inquiry $record): string => $record?->subject ?? '-'),
                                Placeholder::make('message')
                                    ->content(fn (?Inquiry $record): string => $record?->message ?? '-')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Admin Review')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->options(B2BLeadStatus::enquiryOptions())
                                    ->required(),
                                Select::make('assigned_to')
                                    ->label('Assigned owner')
                                    ->options(fn (): array => User::query()
                                        ->whereIn('role', [UserRole::Admin->value, UserRole::Moderator->value])
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->placeholder('Unassigned')
                                    ->searchable()
                                    ->preload(),
                                Textarea::make('internal_notes')
                                    ->label('Internal notes')
                                    ->rows(6)
                                    ->maxLength(5000)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
