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
                    ->searchable()
                    ->copyable(),
                TextColumn::make('lead_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => B2BLeadType::tryFrom($state)?->label() ?? $state),
                TextColumn::make('interest_type')
                    ->label('Interest')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (B2BInterestType::tryFrom($state)?->label() ?? $state) : 'Not specified')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('inquiry_type')
                    ->label('Request')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('application_type')
                    ->label('Application')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('company_name')
                    ->label('Company / Institution')
                    ->searchable()
                    ->description(fn (B2BLead $record): string => collect([
                        $record->organization_type,
                        $record->country,
                        $record->region,
                    ])->filter()->implode(' | ')),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('source_page')
                    ->label('CTA source')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('assignee.name')
                    ->label('Owner')
                    ->placeholder('Unassigned')
                    ->toggleable(),
                TextColumn::make('reviewer.name')
                    ->label('Reviewed by')
                    ->placeholder('Not reviewed')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('lead_type')
                    ->label('Lead type')
                    ->options(B2BLeadType::options()),
                SelectFilter::make('interest_type')
                    ->label('Interest type')
                    ->options(B2BInterestType::options()),
                SelectFilter::make('status')
                    ->options(B2BLeadStatus::options()),
                SelectFilter::make('assigned_to')
                    ->relationship('assignee', 'name')
                    ->label('Owner')
                    ->searchable()
                    ->preload(),
                Filter::make('organization')
                    ->label('Company / Application / Region')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Company / institution'),
                        TextInput::make('application_type')
                            ->label('Application'),
                        TextInput::make('region')
                            ->label('Region'),
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
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Submitted from'),
                        DatePicker::make('created_until')
                            ->label('Submitted until'),
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
                    ->label('Review'),
                Action::make('archive')
                    ->label('Archive')
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
                            ->title('Lead archived.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
