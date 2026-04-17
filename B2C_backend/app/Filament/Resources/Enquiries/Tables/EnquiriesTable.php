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
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('company_name')
                    ->label('Company / Organization')
                    ->searchable()
                    ->description(fn (Inquiry $record): string => collect([
                        $record->organization_type,
                        $record->country,
                        $record->region,
                    ])->filter()->implode(' | ')),
                TextColumn::make('inquiry_type')
                    ->label('Enquiry Type')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('message')
                    ->label('Subject')
                    ->state(fn (Inquiry $record): string => $record->subject)
                    ->searchable()
                    ->limit(50),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->color() ?? 'gray')
                    ->sortable(),
                TextColumn::make('assignee.name')
                    ->label('Assigned Admin')
                    ->placeholder('Unassigned')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(B2BLeadStatus::enquiryOptions()),
                SelectFilter::make('assigned_to')
                    ->relationship('assignee', 'name')
                    ->label('Assigned Admin')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('inquiry_type')
                    ->label('Enquiry Type')
                    ->options(fn (): array => Inquiry::query()
                        ->whereNotNull('inquiry_type')
                        ->select('inquiry_type')
                        ->distinct()
                        ->orderBy('inquiry_type')
                        ->pluck('inquiry_type', 'inquiry_type')
                        ->all()),
                Filter::make('company')
                    ->label('Company / Organization')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Company / organization'),
                        TextInput::make('organization_type')
                            ->label('Organization type'),
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
                            ->label('Email'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['email'] ?? null),
                        fn (Builder $builder): Builder => $builder->where('email', 'like', '%'.trim((string) $data['email']).'%')
                    )),
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
                Action::make('archive')
                    ->label('Archive')
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
                            ->title('Enquiry archived.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
