<?php

namespace App\Filament\Resources\Materials\Tables;

use App\Enums\PublishStatus;
use App\Models\Material;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaterialsTable
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
                    ->defaultImageUrl('https://placehold.co/96x64?text=Material'),
                TextColumn::make('title')
                    ->searchable(['title', 'title_translations->en', 'title_translations->ko', 'title_translations->zh'])
                    ->description(fn (Material $record): string => $record->slug),
                TextColumn::make('headline')
                    ->searchable(['headline', 'headline_translations->en', 'headline_translations->ko', 'headline_translations->zh'])
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PublishStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => PublishStatus::tryFrom($state)?->color() ?? 'gray'),
                IconColumn::make('is_featured')
                    ->label(__('admin.ui.featured'))
                    ->boolean(),
                IconColumn::make('is_seeded')
                    ->label(__('admin.ui.seeded'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('specs_count')
                    ->label(__('admin.ui.specs'))
                    ->badge()
                    ->color('info'),
                TextColumn::make('story_sections_count')
                    ->label(__('admin.ui.story'))
                    ->badge()
                    ->color('warning'),
                TextColumn::make('applications_count')
                    ->label(__('admin.ui.applications'))
                    ->badge()
                    ->color('success'),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PublishStatus::options()),
                TernaryFilter::make('is_featured')
                    ->label(__('admin.ui.featured')),
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
