<?php

namespace App\Filament\Resources\MaterialStorySections\Tables;

use App\Enums\PublishStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaterialStorySectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('media_url')
                    ->label('Media')
                    ->square()
                    ->defaultImageUrl('https://placehold.co/96x64?text=Story'),
                TextColumn::make('material.title')
                    ->label('Material')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('subtitle')
                    ->searchable(),
                TextColumn::make('highlight')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PublishStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => PublishStatus::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('media_path')
                    ->searchable(),
                TextColumn::make('media_url')
                    ->searchable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('material_id')
                    ->relationship('material', 'title')
                    ->label('Material')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(PublishStatus::options()),
                Filter::make('updated_at')
                    ->schema([
                        DatePicker::make('updated_from')
                            ->label('Updated from'),
                        DatePicker::make('updated_until')
                            ->label('Updated until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['updated_from'] ?? null, fn (Builder $builder, string $date): Builder => $builder->whereDate('updated_at', '>=', $date))
                            ->when($data['updated_until'] ?? null, fn (Builder $builder, string $date): Builder => $builder->whereDate('updated_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
