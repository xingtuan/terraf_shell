<?php

namespace App\Filament\Resources\B2BLeads\Schemas;

use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use App\Enums\UserRole;
use App\Models\B2BLead;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class B2BLeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lead Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('reference')
                                    ->content(fn (?B2BLead $record): string => $record?->reference ?? 'Generated automatically'),
                                Placeholder::make('lead_type')
                                    ->label('Lead type')
                                    ->content(fn (?B2BLead $record): string => $record ? (B2BLeadType::tryFrom($record->lead_type)?->label() ?? $record->lead_type) : '-'),
                                Placeholder::make('interest_type')
                                    ->label('Interest type')
                                    ->content(fn (?B2BLead $record): string => $record?->interest_type ?: 'Not specified.'),
                                Placeholder::make('application_type')
                                    ->label('Application')
                                    ->content(fn (?B2BLead $record): string => $record?->application_type ?: 'Not specified.'),
                                Placeholder::make('name')
                                    ->content(fn (?B2BLead $record): string => $record?->name ?? '-'),
                                Placeholder::make('company_name')
                                    ->label('Company / Institution')
                                    ->content(fn (?B2BLead $record): string => $record?->company_name ?? '-'),
                                Placeholder::make('email')
                                    ->label('Email')
                                    ->content(fn (?B2BLead $record): string => $record?->email ?? '-'),
                                Placeholder::make('phone')
                                    ->content(fn (?B2BLead $record): string => $record?->phone ?: 'No phone provided.'),
                                Placeholder::make('organization_type')
                                    ->content(fn (?B2BLead $record): string => $record?->organization_type ?: 'Not specified.'),
                                Placeholder::make('region')
                                    ->content(fn (?B2BLead $record): string => $record?->region ?: 'Not specified.'),
                                Placeholder::make('source_page')
                                    ->content(fn (?B2BLead $record): string => $record?->source_page ?: 'No source page tracked.'),
                                Placeholder::make('company_website')
                                    ->label('Website')
                                    ->content(fn (?B2BLead $record): string => $record?->company_website ?: 'No website provided.'),
                                Placeholder::make('estimated_quantity')
                                    ->label('Estimated quantity')
                                    ->content(fn (?B2BLead $record): string => $record?->estimated_quantity ?: 'Not specified.'),
                                Placeholder::make('timeline')
                                    ->content(fn (?B2BLead $record): string => $record?->timeline ?: 'Not specified.'),
                                Placeholder::make('expected_use_case')
                                    ->label('Expected use case')
                                    ->content(fn (?B2BLead $record): string => $record?->expected_use_case ?: 'Not specified.')
                                    ->columnSpanFull(),
                                Placeholder::make('message')
                                    ->content(fn (?B2BLead $record): string => $record?->message ?? '-')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Detail Records')
                    ->schema([
                        Placeholder::make('partnership_details')
                            ->label('Partnership details')
                            ->content(function (?B2BLead $record): string {
                                if ($record?->partnershipInquiry === null) {
                                    return 'No partnership detail record attached.';
                                }

                                return collect([
                                    'Collaboration type: '.$record->partnershipInquiry->collaboration_type,
                                    'Goal: '.$record->partnershipInquiry->collaboration_goal,
                                    'Stage: '.($record->partnershipInquiry->project_stage ?: 'Not specified'),
                                    'Timeline: '.($record->partnershipInquiry->timeline ?: 'Not specified'),
                                ])->implode("\n");
                            })
                            ->columnSpanFull(),
                        Placeholder::make('sample_request_details')
                            ->label('Sample request details')
                            ->content(function (?B2BLead $record): string {
                                if ($record?->sampleRequest === null) {
                                    return 'No sample request detail record attached.';
                                }

                                return collect([
                                    'Material interest: '.$record->sampleRequest->material_interest,
                                    'Quantity: '.($record->sampleRequest->quantity_estimate ?: 'Not specified'),
                                    'Ship to: '.collect([
                                        $record->sampleRequest->shipping_country,
                                        $record->sampleRequest->shipping_region,
                                    ])->filter()->implode(', '),
                                    'Intended use: '.$record->sampleRequest->intended_use,
                                ])->implode("\n");
                            })
                            ->columnSpanFull(),
                    ]),
                Section::make('Admin Review')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->options(B2BLeadStatus::options())
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
                                    ->rows(6)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
