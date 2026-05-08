<?php

namespace App\Filament\Resources\Enquiries\Tables;

use App\Enums\B2BLeadStatus;
use App\Filament\Support\PanelAccess;
use App\Models\Inquiry;
use App\Services\InquiryService;
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

class EnquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('email')
                    ->label(__('admin.ui.contact'))
                    ->searchable()
                    ->copyable()
                    ->description(fn (Inquiry $record): string => collect([$record->name, $record->phone])->filter()->implode(' | ')),
                TextColumn::make('company_name')
                    ->label(__('admin.ui.company_organization'))
                    ->searchable()
                    ->description(fn (Inquiry $record): string => collect([
                        $record->organization_type,
                        $record->country,
                        $record->region,
                    ])->filter()->implode(' | ')),
                TextColumn::make('message')
                    ->label(__('admin.ui.subject'))
                    ->state(fn (Inquiry $record): string => $record->subject)
                    ->description(fn (Inquiry $record): string => collect([
                        $record->inquiry_type ?: 'General enquiry',
                        $record->source_page,
                    ])->filter()->implode(' | '))
                    ->searchable()
                    ->wrap()
                    ->limit(70),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->color() ?? 'gray')
                    ->sortable(),
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
                TextColumn::make('assignee.name')
                    ->label(__('admin.ui.owner'))
                    ->placeholder(__('admin.ui.unassigned'))
                    ->toggleable(),
                TextColumn::make('follow_up_at')
                    ->label(__('admin.fields.follow_up_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('source_page')
                    ->label(__('admin.ui.source'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('admin.ui.submitted'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(B2BLeadStatus::enquiryOptions()),
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
                SelectFilter::make('inquiry_type')
                    ->label(__('admin.ui.enquiry_type_2'))
                    ->options(fn (): array => Inquiry::query()
                        ->whereNotNull('inquiry_type')
                        ->select('inquiry_type')
                        ->distinct()
                        ->orderBy('inquiry_type')
                        ->pluck('inquiry_type', 'inquiry_type')
                        ->all()),
                Filter::make('company')
                    ->label(__('admin.ui.company_organization'))
                    ->schema([
                        TextInput::make('company_name')
                            ->label(__('admin.ui.company_organization_2')),
                        TextInput::make('organization_type')
                            ->label(__('admin.ui.organization_type')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['company_name'] ?? null),
                                fn (Builder $builder): Builder => $builder->where('company_name', 'like', '%'.trim((string) $data['company_name']).'%')
                            )
                            ->when(
                                filled($data['organization_type'] ?? null),
                                fn (Builder $builder): Builder => $builder->where('organization_type', 'like', '%'.trim((string) $data['organization_type']).'%')
                            );
                    }),
                Filter::make('email')
                    ->schema([
                        TextInput::make('email')
                            ->label(__('admin.ui.email')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['email'] ?? null),
                        fn (Builder $builder): Builder => $builder->where('email', 'like', '%'.trim((string) $data['email']).'%')
                    )),
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
                EditAction::make(),
                Action::make('followUp')
                    ->label(__('admin.actions.add_note'))
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        DateTimePicker::make('follow_up_at')
                            ->label(__('admin.fields.follow_up_at')),
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
                    ->fillForm(fn (Inquiry $record): array => [
                        'follow_up_at' => $record->follow_up_at,
                        'priority' => $record->priority ?: 'normal',
                        'internal_notes' => $record->internal_notes,
                    ])
                    ->action(function (Inquiry $record, array $data): void {
                        app(InquiryService::class)->updateForAdmin($record, $data, PanelAccess::user());

                        Notification::make()
                            ->title(__('admin.notifications.lead_updated'))
                            ->success()
                            ->send();
                    }),
                Action::make('archive')
                    ->label(__('admin.actions.archive'))
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Inquiry $record): bool => $record->status !== B2BLeadStatus::Archived->value)
                    ->action(function (Inquiry $record): void {
                        app(InquiryService::class)->updateForAdmin(
                            $record,
                            ['status' => B2BLeadStatus::Archived->value],
                            PanelAccess::user(),
                        );

                        Notification::make()
                            ->title(__('admin.ui.enquiry_archived'))
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
