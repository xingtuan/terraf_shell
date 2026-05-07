<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use Filament\Actions\BulkAction;
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
                ->with(['category', 'variants'])
                ->orderByDesc('featured')
                ->orderByDesc('is_bestseller')
                ->orderBy('sort_order')
                ->orderByDesc('published_at')
                ->orderByDesc('created_at'))
            ->columns([
                ImageColumn::make('image_url')
                    ->label(__('admin.fields.image'))
                    ->square()
                    ->defaultImageUrl('https://placehold.co/96x96?text=Product'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record): string => collect([
                        $record->effectiveSku(),
                        $record->slug,
                    ])->filter()->implode(' | ')),
                TextColumn::make('category.name')
                    ->label(__('admin.resources.product_category'))
                    ->badge()
                    ->placeholder(__('admin.placeholders.uncategorized'))
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->state(fn (Product $record): ?string => $record->effectiveSku())
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ProductStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => ProductStatus::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('stock_status')
                    ->label(__('admin.fields.stock'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state, Product $record): string => filled($record->effectiveStockStatus()) ? __("admin.products.stock_status.{$record->effectiveStockStatus()}") : __('admin.placeholders.unknown'))
                    ->color(fn (?string $state, Product $record): string => match ($record->effectiveStockStatus()) {
                        'in_stock' => 'success',
                        'low_stock' => 'warning',
                        'preorder', 'made_to_order' => 'info',
                        'sold_out' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('price_usd')
                    ->label(__('admin.fields.price').' (NZD)')
                    ->formatStateUsing(fn ($state, Product $record): string => '$'.number_format((float) ($record->effectivePrice() ?? $state), 2)),
                TextColumn::make('stock_quantity')
                    ->label(__('admin.fields.quantity'))
                    ->state(fn (Product $record): ?int => $record->effectiveStockQuantity())
                    ->numeric()
                    ->sortable(),
                IconColumn::make('featured')
                    ->label(__('admin.fields.featured'))
                    ->boolean(),
                IconColumn::make('is_bestseller')
                    ->label(__('admin.fields.bestseller'))
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_new')
                    ->label(__('admin.fields.new_arrival'))
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('inquiry_only')
                    ->label(__('admin.fields.inquiry_only'))
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label(__('admin.fields.published'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('admin.placeholders.draft')),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label(__('admin.resources.product_category'))
                    ->options(fn (): array => ProductCategory::query()
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(ProductStatus::options()),
                SelectFilter::make('stock_status')
                    ->label(__('admin.fields.stock_status'))
                    ->options(fn (): array => self::stockStatusOptions()),
                TernaryFilter::make('featured')
                    ->label(__('admin.fields.featured')),
                TernaryFilter::make('is_bestseller')
                    ->label(__('admin.fields.bestseller')),
                TernaryFilter::make('is_new')
                    ->label(__('admin.fields.new_arrival')),
                TernaryFilter::make('inquiry_only')
                    ->label(__('admin.fields.inquiry_only')),
                TernaryFilter::make('sample_request_enabled')
                    ->label(__('admin.fields.sample_request_enabled')),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('admin.actions.manage')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label(__('admin.actions.activate'))
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    BulkAction::make('deactivate')
                        ->label(__('admin.actions.deactivate'))
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                    BulkAction::make('mark_featured')
                        ->label(__('admin.actions.mark_featured'))
                        ->action(fn ($records) => $records->each->update(['featured' => true])),
                    BulkAction::make('mark_sold_out')
                        ->label(__('admin.actions.mark_sold_out'))
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update([
                            'stock_status' => 'sold_out',
                            'stock_quantity' => 0,
                            'in_stock' => false,
                        ])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function stockStatusOptions(): array
    {
        return collect(array_keys(Product::STOCK_STATUS_OPTIONS))
            ->mapWithKeys(fn (string $status): array => [$status => __("admin.products.stock_status.{$status}")])
            ->all();
    }
}
