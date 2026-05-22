<?php

namespace App\Filament\Resources\MaterialApplications\Tables;

use App\Enums\PublishStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
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

class MaterialApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('media_url')
                    ->label(__('admin.ui.media'))
                    ->square()
                    ->defaultImageUrl('https://placehold.co/96x64?text=Use'),
                TextColumn::make('material.title')
                    ->label(__('admin.ui.material'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('admin.ui.title'))
                    ->searchable(),
                TextColumn::make('subtitle')
                    ->label(__('admin.ui.subtitle'))
                    ->searchable(),
                TextColumn::make('audience')
                    ->label(__('admin.ui.audience'))
                    ->searchable(),
                TextColumn::make('cta_label')
                    ->label(__('admin.ui.cta_label'))
                    ->searchable(),
                TextColumn::make('cta_url')
                    ->label(__('admin.ui.cta_url'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PublishStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => PublishStatus::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('sort_order')
                    ->label(__('admin.ui.sort_order'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('media_path')
                    ->label(__('admin.fields.path'))
                    ->searchable(),
                TextColumn::make('media_url')
                    ->label(__('admin.ui.external_media_url'))
                    ->searchable(),
                TextColumn::make('published_at')
                    ->label(__('admin.ui.published_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('admin.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('material_id')
                    ->relationship('material', 'title')
                    ->label(__('admin.ui.material'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(PublishStatus::options()),
                Filter::make('updated_at')
                    ->schema([
                        DatePicker::make('updated_from')
                            ->label(__('admin.ui.updated_from')),
                        DatePicker::make('updated_until')
                            ->label(__('admin.ui.updated_until')),
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
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
