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
                                    ->label(__('admin.ui.reference'))
                                    ->content(fn (?B2BLead $record): string => $record?->reference ?? __('admin.placeholders.generated_automatically')),
                                Placeholder::make('lead_type')
                                    ->label(__('admin.ui.lead_type'))
                                    ->content(fn (?B2BLead $record): string => $record ? (B2BLeadType::tryFrom($record->lead_type)?->label() ?? $record->lead_type) : '-'),
                                Placeholder::make('interest_type')
                                    ->label(__('admin.ui.interest_type'))
                                    ->content(fn (?B2BLead $record): string => $record?->interest_type ?: __('admin.ui.not_specified')),
                                Placeholder::make('application_type')
                                    ->label(__('admin.ui.application'))
                                    ->content(fn (?B2BLead $record): string => $record?->application_type ?: __('admin.ui.not_specified')),
                                Placeholder::make('name')
                                    ->label(__('admin.fields.name'))
                                    ->content(fn (?B2BLead $record): string => $record?->name ?? '-'),
                                Placeholder::make('company_name')
                                    ->label(__('admin.ui.company_institution'))
                                    ->content(fn (?B2BLead $record): string => $record?->company_name ?? '-'),
                                Placeholder::make('email')
                                    ->label(__('admin.ui.email'))
                                    ->content(fn (?B2BLead $record): string => $record?->email ?? '-'),
                                Placeholder::make('phone')
                                    ->label(__('admin.fields.phone'))
                                    ->content(fn (?B2BLead $record): string => $record?->phone ?: __('admin.ui.no_phone_provided')),
                                Placeholder::make('organization_type')
                                    ->label(__('admin.ui.organization_type'))
                                    ->content(fn (?B2BLead $record): string => $record?->organization_type ?: __('admin.ui.not_specified')),
                                Placeholder::make('region')
                                    ->label(__('admin.fields.region'))
                                    ->content(fn (?B2BLead $record): string => $record?->region ?: __('admin.ui.not_specified')),
                                Placeholder::make('source_page')
                                    ->label(__('admin.ui.source'))
                                    ->content(fn (?B2BLead $record): string => $record?->source_page ?: __('admin.ui.no_source_page_tracked')),
                                Placeholder::make('company_website')
                                    ->label(__('admin.ui.website'))
                                    ->content(fn (?B2BLead $record): string => $record?->company_website ?: __('admin.ui.no_website_provided')),
                                Placeholder::make('estimated_quantity')
                                    ->label(__('admin.ui.estimated_quantity'))
                                    ->content(fn (?B2BLead $record): string => $record?->estimated_quantity ?: __('admin.ui.not_specified')),
                                Placeholder::make('timeline')
                                    ->label(__('admin.ui.lead_time'))
                                    ->content(fn (?B2BLead $record): string => $record?->timeline ?: __('admin.ui.not_specified')),
                                Placeholder::make('expected_use_case')
                                    ->label(__('admin.ui.expected_use_case'))
                                    ->content(fn (?B2BLead $record): string => $record?->expected_use_case ?: __('admin.ui.not_specified'))
                                    ->columnSpanFull(),
                                Placeholder::make('message')
                                    ->label(__('admin.fields.message'))
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
                                    return __('admin.ui.no_partnership_detail_attached');
                                }

                                return collect([
                                    __('admin.labels.field_value', ['field' => __('admin.ui.collaboration_type'), 'value' => $record->partnershipInquiry->collaboration_type ?: __('admin.ui.not_specified')]),
                                    __('admin.labels.field_value', ['field' => __('admin.ui.collaboration_goal'), 'value' => $record->partnershipInquiry->collaboration_goal ?: __('admin.ui.not_specified')]),
                                    __('admin.labels.field_value', ['field' => __('admin.ui.stage'), 'value' => $record->partnershipInquiry->project_stage ?: __('admin.ui.not_specified')]),
                                    __('admin.labels.field_value', ['field' => __('admin.sections.timeline'), 'value' => $record->partnershipInquiry->timeline ?: __('admin.ui.not_specified')]),
                                ])->implode("\n");
                            })
                            ->columnSpanFull(),
                        Placeholder::make('sample_request_details')
                            ->label(__('admin.ui.sample_request_details'))
                            ->content(function (?B2BLead $record): string {
                                if ($record?->sampleRequest === null) {
                                    return __('admin.ui.no_sample_request_detail_attached');
                                }

                                $shipTo = collect([
                                    $record->sampleRequest->shipping_country,
                                    $record->sampleRequest->shipping_region,
                                ])->filter()->implode(', ');

                                return collect([
                                    __('admin.labels.field_value', ['field' => __('admin.ui.material_interest'), 'value' => $record->sampleRequest->material_interest ?: __('admin.ui.not_specified')]),
                                    __('admin.labels.field_value', ['field' => __('admin.fields.quantity'), 'value' => $record->sampleRequest->quantity_estimate ?: __('admin.ui.not_specified')]),
                                    __('admin.labels.field_value', ['field' => __('admin.ui.ship_to'), 'value' => $shipTo !== '' ? $shipTo : __('admin.ui.not_specified')]),
                                    __('admin.labels.field_value', ['field' => __('admin.ui.intended_use'), 'value' => $record->sampleRequest->intended_use ?: __('admin.ui.not_specified')]),
                                ])->implode("\n");
                            })
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.admin_review'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label(__('admin.fields.status'))
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
                                    ->label(__('admin.ui.internal_notes'))
                                    ->rows(6)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
