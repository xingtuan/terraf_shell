<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with('category')
                ->orderByDesc('featured')
                ->orderByDesc('is_bestseller')
                ->orderBy('sort_order')
                ->orderByDesc('published_at')
                ->orderByDesc('created_at'))
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Image')
                    ->square()
                    ->defaultImageUrl('https://placehold.co/96x96?text=Product'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record): string => collect([
                        $record->sku,
                        $record->slug,
                    ])->filter()->implode(' | ')),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->placeholder('Uncategorized')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ProductStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => ProductStatus::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('stock_status')
                    ->label('Stock')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Product::labelForOption(Product::STOCK_STATUS_OPTIONS, $state) ?? 'Unknown')
                    ->color(fn (?string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'low_stock' => 'warning',
                        'preorder', 'made_to_order' => 'info',
                        'sold_out' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('price_usd')
                    ->label('Price')
                    ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
                IconColumn::make('featured')
                    ->label('Featured')
                    ->boolean(),
                IconColumn::make('inquiry_only')
                    ->label('Inquiry only')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Draft'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn (): array => ProductCategory::query()
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(ProductStatus::options()),
                SelectFilter::make('stock_status')
                    ->label('Stock status')
                    ->options(Product::STOCK_STATUS_OPTIONS),
                TernaryFilter::make('featured')
                    ->label('Featured'),
                TernaryFilter::make('inquiry_only')
                    ->label('Inquiry only'),
                TernaryFilter::make('sample_request_enabled')
                    ->label('Sample request enabled'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Manage'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
