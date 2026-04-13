<?php

namespace App\Filament\Resources\B2BLeads\Tables;

use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
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
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('lead_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => B2BLeadType::tryFrom($state)?->label() ?? $state),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('company_name')
                    ->label('Company / Institution')
                    ->searchable()
                    ->description(fn ($record): string => collect([$record->organization_type, $record->region])->filter()->implode(' · ')),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('source_page')
                    ->label('CTA Source')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('reviewer.name')
                    ->label('Reviewed by')
                    ->placeholder('Unassigned')
                    ->toggleable(),
                TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('lead_type')
                    ->label('Lead type')
                    ->options(B2BLeadType::options()),
                SelectFilter::make('status')
                    ->options(B2BLeadStatus::options()),
                Filter::make('organization')
                    ->label('Company / Region')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Company / institution'),
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
                EditAction::make(),
            ]);
    }
}
