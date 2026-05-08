<?php

namespace App\Filament\Resources\B2BLeads\Schemas;

use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use App\Enums\UserRole;
use App\Models\B2BLead;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
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
                Section::make(__('admin.ui.lead_details'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('reference')
                                    ->content(fn (?B2BLead $record): string => $record?->reference ?? 'Generated automatically'),
                                Placeholder::make('lead_type')
                                    ->label(__('admin.ui.lead_type'))
                                    ->content(fn (?B2BLead $record): string => $record ? (B2BLeadType::tryFrom($record->lead_type)?->label() ?? $record->lead_type) : '-'),
                                Placeholder::make('interest_type')
                                    ->label(__('admin.ui.interest_type'))
                                    ->content(fn (?B2BLead $record): string => $record?->interest_type ?: 'Not specified.'),
                                Placeholder::make('application_type')
                                    ->label(__('admin.ui.application'))
                                    ->content(fn (?B2BLead $record): string => $record?->application_type ?: 'Not specified.'),
                                Placeholder::make('name')
                                    ->content(fn (?B2BLead $record): string => $record?->name ?? '-'),
                                Placeholder::make('company_name')
                                    ->label(__('admin.ui.company_institution'))
                                    ->content(fn (?B2BLead $record): string => $record?->company_name ?? '-'),
                                Placeholder::make('email')
                                    ->label(__('admin.ui.email'))
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
                                    ->label(__('admin.ui.website'))
                                    ->content(fn (?B2BLead $record): string => $record?->company_website ?: 'No website provided.'),
                                Placeholder::make('estimated_quantity')
                                    ->label(__('admin.ui.estimated_quantity'))
                                    ->content(fn (?B2BLead $record): string => $record?->estimated_quantity ?: 'Not specified.'),
                                Placeholder::make('timeline')
                                    ->content(fn (?B2BLead $record): string => $record?->timeline ?: 'Not specified.'),
                                Placeholder::make('expected_use_case')
                                    ->label(__('admin.ui.expected_use_case'))
                                    ->content(fn (?B2BLead $record): string => $record?->expected_use_case ?: 'Not specified.')
                                    ->columnSpanFull(),
                                Placeholder::make('message')
                                    ->content(fn (?B2BLead $record): string => $record?->message ?? '-')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make(__('admin.ui.detail_records'))
                    ->schema([
                        Placeholder::make('partnership_details')
                            ->label(__('admin.ui.partnership_details'))
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
                            ->label(__('admin.ui.sample_request_details'))
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
                Section::make(__('admin.ui.admin_review'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->options(B2BLeadStatus::options())
                                    ->required(),
                                Select::make('priority')
                                    ->label(__('admin.fields.priority'))
                                    ->options([
                                        'low' => __('admin.leads.priority.low'),
                                        'normal' => __('admin.leads.priority.normal'),
                                        'high' => __('admin.leads.priority.high'),
                                        'urgent' => __('admin.leads.priority.urgent'),
                                    ])
                                    ->default('normal')
                                    ->required(),
                                Select::make('assigned_to')
                                    ->label(__('admin.ui.assigned_owner'))
                                    ->options(fn (): array => User::query()
                                        ->whereIn('role', [UserRole::Admin->value, UserRole::Moderator->value])
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->placeholder(__('admin.ui.unassigned'))
                                    ->searchable()
                                    ->preload(),
                                DateTimePicker::make('follow_up_at')
                                    ->label(__('admin.fields.follow_up_at')),
                                Textarea::make('internal_notes')
                                    ->rows(6)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
