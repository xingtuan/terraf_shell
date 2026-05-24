<?php

namespace App\Filament\Resources\HomeSections\Tables;

use App\Enums\PublishStatus;
use App\Models\HomeSection;
use App\Support\HomeSectionVisibility;
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

class HomeSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('media_url')
                    ->label(__('admin.ui.media'))
                    ->getStateUsing(fn (HomeSection $record): ?string => self::tableMediaUrl($record))
                    ->square()
                    ->defaultImageUrl('https://placehold.co/96x64?text=Home'),
                TextColumn::make('page_key')
                    ->label(__('admin.home_sections.fields.page'))
                    ->formatStateUsing(fn (?string $state): string => HomeSection::pageKeyLabel($state))
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('key')
                    ->label(__('admin.ui.section_key'))
                    ->searchable(),
                TextColumn::make('title')
                    ->label(__('admin.ui.title'))
                    ->searchable(['title', 'title_translations->en', 'title_translations->ko', 'title_translations->zh'])
                    ->limit(60),
                TextColumn::make('status')
                    ->label(__('admin.home_sections.columns.frontend_visibility'))
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => HomeSectionVisibility::labelFor($state))
                    ->color(fn (mixed $state): string => PublishStatus::colorFor($state)),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label(__('admin.ui.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('admin.fields.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('page_key')
                    ->label(__('admin.home_sections.fields.page'))
                    ->options(HomeSection::pageKeyOptions()),
                SelectFilter::make('status')
                    ->label(__('admin.home_sections.filters.frontend_visibility'))
                    ->options(HomeSectionVisibility::options()),
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

    private static function tableMediaUrl(HomeSection $record): ?string
    {
        $url = $record->media_url;

        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (filter_var($url, FILTER_VALIDATE_URL) !== false || str_starts_with($url, 'data:')) {
            return $url;
        }

        return url('/'.ltrim($url, '/'));
    }
}
