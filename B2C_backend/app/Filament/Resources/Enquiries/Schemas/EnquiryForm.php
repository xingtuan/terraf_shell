<?php

namespace App\Filament\Resources\Enquiries\Schemas;

use App\Enums\B2BLeadStatus;
use App\Enums\UserRole;
use App\Models\Inquiry;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
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
                Section::make(__('admin.ui.enquiry_details'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('reference')
                                    ->content(fn (?Inquiry $record): string => $record?->reference ?? 'Generated automatically'),
                                Placeholder::make('status_badge')
                                    ->label(__('admin.ui.current_status'))
                                    ->content(fn (?Inquiry $record): string => $record ? (B2BLeadStatus::tryFrom($record->status)?->label() ?? $record->status) : '-'),
                                Placeholder::make('name')
                                    ->content(fn (?Inquiry $record): string => $record?->name ?? '-'),
                                Placeholder::make('email')
                                    ->label(__('admin.ui.email'))
                                    ->content(fn (?Inquiry $record): string => $record?->email ?? '-'),
                                Placeholder::make('company_name')
                                    ->label(__('admin.ui.company_organization'))
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
                                    ->label(__('admin.ui.source'))
                                    ->content(fn (?Inquiry $record): string => $record?->source_page ?: 'No source tracked.'),
                                Placeholder::make('inquiry_type')
                                    ->label(__('admin.ui.enquiry_type'))
                                    ->content(fn (?Inquiry $record): string => $record?->inquiry_type ?: 'General enquiry'),
                                Placeholder::make('subject')
                                    ->content(fn (?Inquiry $record): string => $record?->subject ?? '-'),
                                Placeholder::make('message')
                                    ->content(fn (?Inquiry $record): string => $record?->message ?? '-')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make(__('admin.ui.admin_review'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->options(B2BLeadStatus::enquiryOptions())
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
                                    ->maxLength(5000)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
