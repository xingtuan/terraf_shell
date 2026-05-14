<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Throwable;

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
                    ->label(__('admin.fields.name'))
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
                TextColumn::make('default_variant_sku')
                    ->label(__('admin.ui.sku'))
                    ->state(fn (Product $record): ?string => $record->effectiveSku())
                    ->copyable(),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ProductStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => ProductStatus::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('demo_content')
                    ->label('Demo')
                    ->state(fn (Product $record): ?string => self::isDemoProduct($record) ? 'Demo' : null)
                    ->badge()
                    ->color('warning')
                    ->placeholder('Real')
                    ->toggleable(),
                TextColumn::make('default_variant_stock_status')
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
                TextColumn::make('default_variant_price')
                    ->label(__('admin.labels.currency_field', ['field' => __('admin.fields.price'), 'currency' => __('admin.currency.nzd')]))
                    ->state(fn (Product $record): ?float => $record->effectivePrice())
                    ->formatStateUsing(fn ($state): string => $state !== null ? '$'.number_format((float) $state, 2) : '-'),
                TextColumn::make('default_variant_stock_quantity')
                    ->label(__('admin.fields.quantity'))
                    ->state(fn (Product $record): ?int => $record->effectiveStockQuantity())
                    ->numeric(),
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
                    ->label(__('admin.fields.status'))
                    ->options(ProductStatus::options()),
                SelectFilter::make('demo_content')
                    ->label('Demo state')
                    ->options([
                        'demo' => 'Demo products',
                        'real' => 'Real products',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'demo' => self::demoProductQuery($query),
                        'real' => self::realProductQuery($query),
                        default => $query,
                    }),
                SelectFilter::make('variant_stock_status')
                    ->label(__('admin.fields.stock_status'))
                    ->options(fn (): array => self::stockStatusOptions())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('variants', fn (Builder $variantQuery) => $variantQuery
                            ->where('is_active', true)
                            ->where('stock_status', $data['value']))
                        : $query),
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
                DeleteAction::make()
                    ->using(fn (Product $record): bool => self::deleteProductWithDependencyNotice($record)),
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
                        ->action(fn ($records) => $records->each(
                            fn (Product $record) => $record->variants()->update([
                                'stock_status' => 'sold_out',
                                'stock_quantity' => 0,
                            ]),
                        )),
                    BulkAction::make('mark_demo_content')
                        ->label('Mark selected as demo')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn (Product $record) => self::markProductAsDemo($record))),
                    DeleteBulkAction::make()
                        ->using(function (DeleteBulkAction $action, EloquentCollection|Collection|LazyCollection $records): void {
                            $records->each(function (Product $record) use ($action): void {
                                if (self::deleteProductWithDependencyNotice($record, notify: false)) {
                                    return;
                                }

                                $action->reportBulkProcessingFailure(
                                    'product_dependency',
                                    'Some products still have cart items or order history and were not deleted.',
                                );
                            });
                        }),
                ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function stockStatusOptions(): array
    {
        return collect(array_keys(ProductVariant::STOCK_STATUS_OPTIONS))
            ->mapWithKeys(fn (string $status): array => [$status => __("admin.products.stock_status.{$status}")])
            ->all();
    }

    public static function deleteProductWithDependencyNotice(Product $record, bool $notify = true): bool
    {
        $cartItems = $record->cartItems()->count();
        $orderItems = $record->orderItems()->count();

        if ($cartItems > 0 || $orderItems > 0) {
            if ($notify) {
                Notification::make()
                    ->title('Product cannot be deleted')
                    ->body("Remove {$cartItems} cart items and preserve or resolve {$orderItems} order items before deleting this product.")
                    ->danger()
                    ->send();
            }

            return false;
        }

        try {
            return (bool) $record->delete();
        } catch (Throwable) {
            if ($notify) {
                Notification::make()
                    ->title('Product cannot be deleted')
                    ->body('A database constraint is still referencing this product.')
                    ->danger()
                    ->send();
            }

            return false;
        }
    }

    private static function markProductAsDemo(Product $record): void
    {
        $metadata = [
            'is_demo_content' => true,
            'seed_source' => 'admin_marked_demo',
            'seeded_at' => now(),
        ];

        $record->update($metadata);
        $record->images()->update($metadata);
        $record->variants()->update($metadata);
        $record->attributeAssignments()->update($metadata);
    }

    private static function isDemoProduct(Product $record): bool
    {
        return (bool) $record->is_demo_content || filled($record->seed_source);
    }

    private static function demoProductQuery(Builder $query): Builder
    {
        return $query->where(fn (Builder $builder) => $builder
            ->where('is_demo_content', true)
            ->orWhereNotNull('seed_source'));
    }

    private static function realProductQuery(Builder $query): Builder
    {
        return $query
            ->where('is_demo_content', false)
            ->whereNull('seed_source');
    }
}
