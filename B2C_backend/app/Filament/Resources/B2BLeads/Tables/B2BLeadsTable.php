<?php

namespace App\Filament\Resources\B2BLeads\Tables;

use App\Enums\B2BInterestType;
use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use App\Filament\Support\PanelAccess;
use App\Models\B2BLead;
use App\Services\B2BLeadService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class B2BLeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label(__('admin.ui.reference'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('lead_type')
                    ->label(__('admin.ui.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => B2BLeadType::tryFrom($state)?->label() ?? $state),
                TextColumn::make('interest_type')
                    ->label(__('admin.ui.interest'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (B2BInterestType::tryFrom($state)?->label() ?? $state) : __('admin.ui.not_specified'))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('priority')
                    ->label(__('admin.fields.priority'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? __("admin.leads.priority.{$state}") : __('admin.leads.priority.normal'))
                    ->color(fn (?string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'low' => 'gray',
                        default => 'info',
                    })
                    ->sortable(),
                TextColumn::make('inquiry_type')
                    ->label(__('admin.ui.request'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('application_type')
                    ->label(__('admin.ui.application'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable(),
                TextColumn::make('company_name')
                    ->label(__('admin.ui.company_institution'))
                    ->searchable()
                    ->description(fn (B2BLead $record): string => collect([
                        $record->organization_type,
                        $record->country,
                        $record->region,
                    ])->filter()->implode(' | ')),
                TextColumn::make('email')
                    ->label(__('admin.ui.email'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('source_page')
                    ->label(__('admin.ui.cta_source'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('assignee.name')
                    ->label(__('admin.ui.owner'))
                    ->placeholder(__('admin.ui.unassigned'))
                    ->toggleable(),
                TextColumn::make('follow_up_at')
                    ->label(__('admin.fields.follow_up_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-')
                    ->color(fn (?B2BLead $record): string => $record?->follow_up_at !== null && $record->follow_up_at->isPast() ? 'danger' : 'gray')
                    ->toggleable(),
                TextColumn::make('reviewer.name')
                    ->label(__('admin.ui.reviewed_by'))
                    ->placeholder(__('admin.ui.not_reviewed'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reviewed_at')
                    ->label(__('admin.ui.reviewed'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('admin.ui.submitted'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('lead_type')
                    ->label(__('admin.ui.lead_type'))
                    ->options(B2BLeadType::options()),
                SelectFilter::make('interest_type')
                    ->label(__('admin.ui.interest_type'))
                    ->options(B2BInterestType::options()),
                SelectFilter::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(B2BLeadStatus::options()),
                SelectFilter::make('priority')
                    ->label(__('admin.fields.priority'))
                    ->options([
                        'low' => __('admin.leads.priority.low'),
                        'normal' => __('admin.leads.priority.normal'),
                        'high' => __('admin.leads.priority.high'),
                        'urgent' => __('admin.leads.priority.urgent'),
                    ]),
                SelectFilter::make('assigned_to')
                    ->relationship('assignee', 'name')
                    ->label(__('admin.ui.owner'))
                    ->searchable()
                    ->preload(),
                Filter::make('organization')
                    ->label(__('admin.ui.company_application_region'))
                    ->schema([
                        TextInput::make('company_name')
                            ->label(__('admin.ui.company_institution_2')),
                        TextInput::make('application_type')
                            ->label(__('admin.ui.application')),
                        TextInput::make('region')
                            ->label(__('admin.ui.region')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['company_name'] ?? null),
                                fn (Builder $builder): Builder => $builder->where('company_name', 'like', '%'.trim((string) $data['company_name']).'%')
                            )
                            ->when(
                                filled($data['application_type'] ?? null),
                                fn (Builder $builder): Builder => $builder->where('application_type', 'like', '%'.trim((string) $data['application_type']).'%')
                            )
                            ->when(
                                filled($data['region'] ?? null),
                                fn (Builder $builder): Builder => $builder->where('region', 'like', '%'.trim((string) $data['region']).'%')
                            );
                    }),
                Filter::make('overdue_followups')
                    ->label(__('admin.ui.overdue_follow_ups'))
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('follow_up_at')
                        ->where('follow_up_at', '<', now())
                        ->whereNotIn('status', [
                            B2BLeadStatus::Archived->value,
                            B2BLeadStatus::Closed->value,
                            B2BLeadStatus::Resolved->value,
                        ])),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('admin.ui.submitted_from')),
                        DatePicker::make('created_until')
                            ->label(__('admin.ui.submitted_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $builder, string $date): Builder => $builder->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $builder, string $date): Builder => $builder->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->label(__('admin.actions.review')),
                Action::make('assign')
                    ->label(__('admin.actions.assign'))
                    ->icon('heroicon-o-user-plus')
                    ->schema([
                        Select::make('assigned_to')
                            ->label(__('admin.fields.assigned_to'))
                            ->relationship('assignee', 'name')
                            ->searchable()
                            ->preload(),
                        DateTimePicker::make('follow_up_at')
                            ->label(__('admin.fields.follow_up_at')),
                    ])
                    ->fillForm(fn (B2BLead $record): array => [
                        'assigned_to' => $record->assigned_to,
                        'follow_up_at' => $record->follow_up_at,
                    ])
                    ->action(function (B2BLead $record, array $data): void {
                        app(B2BLeadService::class)->updateForAdmin($record, $data, PanelAccess::user());

                        Notification::make()
                            ->title(__('admin.notifications.lead_updated'))
                            ->success()
                            ->send();
                    }),
                Action::make('changeStatus')
                    ->label(__('admin.actions.change_status'))
                    ->icon('heroicon-o-adjustments-horizontal')
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
                            ->required(),
                        Textarea::make('internal_notes')
                            ->label(__('admin.fields.admin_note'))
                            ->rows(4),
                    ])
                    ->fillForm(fn (B2BLead $record): array => [
                        'status' => $record->status,
                        'priority' => $record->priority ?: 'normal',
                        'internal_notes' => $record->internal_notes,
                    ])
                    ->action(function (B2BLead $record, array $data): void {
                        app(B2BLeadService::class)->updateForAdmin($record, $data, PanelAccess::user());

                        Notification::make()
                            ->title(__('admin.notifications.lead_updated'))
                            ->success()
                            ->send();
                    }),
                Action::make('resolve')
                    ->label(__('admin.actions.mark_resolved'))
                    ->color('success')
                    ->visible(fn (B2BLead $record): bool => $record->status !== B2BLeadStatus::Resolved->value)
                    ->action(function (B2BLead $record): void {
                        app(B2BLeadService::class)->updateForAdmin(
                            $record,
                            ['status' => B2BLeadStatus::Resolved->value],
                            PanelAccess::user(),
                        );

                        Notification::make()
                            ->title(__('admin.notifications.lead_updated'))
                            ->success()
                            ->send();
                    }),
                Action::make('archive')
                    ->label(__('admin.actions.archive'))
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (B2BLead $record): bool => $record->status !== B2BLeadStatus::Archived->value)
                    ->action(function (B2BLead $record): void {
                        app(B2BLeadService::class)->updateForAdmin(
                            $record,
                            ['status' => B2BLeadStatus::Archived->value],
                            PanelAccess::user(),
                        );

                        Notification::make()
                            ->title(__('admin.notifications.lead_archived'))
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
