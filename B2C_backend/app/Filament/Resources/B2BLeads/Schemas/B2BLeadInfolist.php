<?php

namespace App\Filament\Resources\B2BLeads\Schemas;

use App\Enums\B2BInterestType;
use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Lang;

class B2BLeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.lead'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('reference')
                                    ->label(__('admin.ui.reference')),
                                TextEntry::make('lead_type')
                                    ->label(__('admin.ui.lead_type'))
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => B2BLeadType::tryFrom($state)?->label() ?? $state),
                                TextEntry::make('interest_type')
                                    ->label(__('admin.ui.interest_type'))
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => $state ? (B2BInterestType::tryFrom($state)?->label() ?? $state) : __('admin.ui.not_specified'))
                                    ->placeholder(__('admin.ui.not_specified')),
                                TextEntry::make('status')
                                    ->label(__('admin.fields.status'))
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->label() ?? $state)
                                    ->color(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('priority')
                                    ->label(__('admin.fields.priority'))
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => $state ? __("admin.leads.priority.{$state}") : __('admin.leads.priority.normal')),
                                TextEntry::make('inquiry_type')
                                    ->label(__('admin.ui.enquiry_type'))
                                    ->formatStateUsing(fn (?string $state): string => $state ? (Lang::has("admin.leads.type.{$state}") ? __("admin.leads.type.{$state}") : $state) : __('admin.ui.general_enquiry'))
                                    ->placeholder(__('admin.ui.general_enquiry')),
                                TextEntry::make('application_type')
                                    ->label(__('admin.ui.application'))
                                    ->placeholder(__('admin.ui.not_specified')),
                                TextEntry::make('name')
                                    ->label(__('admin.fields.name')),
                                TextEntry::make('company_name')
                                    ->label(__('admin.ui.company_institution')),
                                TextEntry::make('organization_type')
                                    ->label(__('admin.ui.organization_type'))
                                    ->placeholder(__('admin.ui.not_specified')),
                                TextEntry::make('job_title')
                                    ->label(__('admin.ui.job_title'))
                                    ->placeholder(__('admin.ui.not_specified')),
                                TextEntry::make('email')
                                    ->label(__('admin.ui.email')),
                                TextEntry::make('phone')
                                    ->label(__('admin.fields.phone'))
                                    ->placeholder(__('admin.ui.not_provided')),
                                TextEntry::make('country')
                                    ->label(__('admin.fields.country'))
                                    ->placeholder(__('admin.ui.not_provided')),
                                TextEntry::make('region')
                                    ->label(__('admin.fields.region'))
                                    ->placeholder(__('admin.ui.not_provided')),
                                TextEntry::make('company_website')
                                    ->label(__('admin.ui.website'))
                                    ->placeholder(__('admin.ui.not_provided'))
                                    ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                                    ->openUrlInNewTab(),
                                TextEntry::make('source_page')
                                    ->label(__('admin.ui.cta_source'))
                                    ->placeholder(__('admin.ui.not_tracked')),
                                TextEntry::make('estimated_quantity')
                                    ->label(__('admin.ui.estimated_quantity'))
                                    ->placeholder(__('admin.ui.not_specified')),
                                TextEntry::make('timeline')
                                    ->label(__('admin.ui.lead_time'))
                                    ->placeholder(__('admin.ui.not_specified')),
                                TextEntry::make('expected_use_case')
                                    ->label(__('admin.ui.expected_use_case'))
                                    ->placeholder(__('admin.ui.not_specified'))
                                    ->columnSpanFull(),
                                TextEntry::make('message')
                                    ->label(__('admin.fields.message'))
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make(__('admin.ui.review'))
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
                            ->placeholder(__('admin.ui.unassigned_2')),
                        TextEntry::make('reviewed_at')
                            ->label(__('admin.ui.reviewed_at'))
                            ->dateTime()
                            ->placeholder(__('admin.ui.not_reviewed_yet')),
                        TextEntry::make('created_at')
                            ->label(__('admin.ui.submitted_at'))
                            ->dateTime(),
                    ]),
                Section::make(__('admin.ui.structured_detail'))
                    ->schema([
                        TextEntry::make('partnershipInquiry.collaboration_type')
                            ->label(__('admin.ui.collaboration_type'))
                            ->placeholder(__('admin.ui.no_partnership_detail_attached')),
                        TextEntry::make('partnershipInquiry.collaboration_goal')
                            ->label(__('admin.ui.collaboration_goal'))
                            ->placeholder(__('admin.ui.no_partnership_detail_attached'))
                            ->columnSpanFull(),
                        TextEntry::make('sampleRequest.material_interest')
                            ->label(__('admin.ui.material_interest'))
                            ->placeholder(__('admin.ui.no_sample_request_detail_attached')),
                        TextEntry::make('sampleRequest.intended_use')
                            ->label(__('admin.ui.intended_use'))
                            ->placeholder(__('admin.ui.no_sample_request_detail_attached'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
