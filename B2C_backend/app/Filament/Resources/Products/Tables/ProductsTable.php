<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use Filament\Actions\Action;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use RuntimeException;
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
                self::archiveAction(),
                self::deleteAction(),
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
                    DeleteBulkAction::make()
                        ->modalDescription(fn ($records = null): string => self::bulkDeleteConfirmationDescription($records ?? collect()))
                        ->using(function (DeleteBulkAction $action, EloquentCollection|Collection|LazyCollection $records): void {
                            $records->each(function (Product $record) use ($action): void {
                                $dependencyCounts = self::dependencyCounts($record);

                                if ($dependencyCounts['order_items'] > 0) {
                                    $action->reportBulkProcessingFailure(
                                        'order_history',
                                        fn (int $failureCount): string => "{$failureCount} selected product(s) have order history and were skipped. Use Archive to hide them from the storefront while preserving orders.",
                                    );

                                    return;
                                }

                                if (self::deleteProductWithDependencyNotice($record, notify: false)) {
                                    return;
                                }

                                $action->reportBulkProcessingFailure(
                                    'product_delete_failed',
                                    fn (int $failureCount): string => "{$failureCount} selected product(s) could not be deleted because another database constraint or server error still references them.",
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

    public static function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->modalDescription(fn (Product $record): string => self::deleteConfirmationDescription($record))
            ->successNotification(null)
            ->using(fn (Product $record): bool => self::deleteProductWithDependencyNotice($record));
    }

    public static function archiveAction(): Action
    {
        return Action::make('archive')
            ->label(__('admin.actions.archive'))
            ->icon('heroicon-o-archive-box')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Archive product')
            ->modalDescription('Archive this product to preserve order history while hiding it from the storefront.')
            ->visible(fn (Product $record): bool => $record->orderItems()->exists())
            ->action(function (Product $record, $livewire = null): void {
                self::archiveProductWithDependencyNotice($record);

                if (is_object($livewire) && method_exists($livewire, 'refreshFormData')) {
                    $livewire->refreshFormData(['status', 'is_active', 'featured', 'variants']);
                }
            });
    }

    public static function deleteProductWithDependencyNotice(Product $record, bool $notify = true): bool
    {
        $dependencyCounts = self::dependencyCounts($record);
        $orderItems = $dependencyCounts['order_items'];

        if ($orderItems > 0) {
            if ($notify) {
                Notification::make()
                    ->title('Product has order history')
                    ->body("This product appears in {$orderItems} order item(s). Hard deletion is disabled; archive the product to hide it from the storefront while preserving order history.")
                    ->danger()
                    ->send();
            }

            return false;
        }

        try {
            $deletedCartItems = DB::transaction(function () use ($record): int {
                $deletedCartItems = $record->cartItems()->delete();

                if (! $record->delete()) {
                    throw new RuntimeException('Product delete returned false.');
                }

                return $deletedCartItems;
            });

            if ($notify) {
                Notification::make()
                    ->title('Product deleted')
                    ->body("Product deleted and removed from {$deletedCartItems} cart item(s).")
                    ->success()
                    ->send();
            }

            return true;
        } catch (Throwable $exception) {
            report($exception);

            if ($notify) {
                Notification::make()
                    ->title('Product cannot be deleted')
                    ->body('The product could not be deleted because another database constraint or server error still references it.')
                    ->danger()
                    ->send();
            }

            return false;
        }
    }

    public static function archiveProductWithDependencyNotice(Product $record, bool $notify = true): bool
    {
        try {
            DB::transaction(function () use ($record): void {
                $record->forceFill([
                    'status' => ProductStatus::Archived->value,
                    'is_active' => false,
                    'featured' => false,
                ])->save();

                $record->variants()->update([
                    'is_active' => false,
                ]);
            });

            if ($notify) {
                Notification::make()
                    ->title('Product archived')
                    ->body('Product archived and hidden from storefront.')
                    ->success()
                    ->send();
            }

            return true;
        } catch (Throwable $exception) {
            report($exception);

            if ($notify) {
                Notification::make()
                    ->title('Product cannot be archived')
                    ->body('The product could not be archived because a database or server error occurred.')
                    ->danger()
                    ->send();
            }

            return false;
        }
    }

    /**
     * @return array{cart_items: int, order_items: int}
     */
    private static function dependencyCounts(Product $record): array
    {
        return [
            'cart_items' => $record->cartItems()->count(),
            'order_items' => $record->orderItems()->count(),
        ];
    }

    private static function deleteConfirmationDescription(Product $record): string
    {
        $dependencyCounts = self::dependencyCounts($record);
        $cartItems = $dependencyCounts['cart_items'];
        $orderItems = $dependencyCounts['order_items'];

        if ($orderItems > 0) {
            return "This product is in {$cartItems} cart item(s) and {$orderItems} order item(s). Hard deletion is disabled because order history exists; use Archive to hide it from the storefront.";
        }

        return "This product is in {$cartItems} cart item(s), which will be removed. No order history exists, so hard deletion is available.";
    }

    private static function bulkDeleteConfirmationDescription(iterable $records): string
    {
        $cartItems = 0;
        $productsWithOrderHistory = 0;

        foreach ($records as $record) {
            if (! $record instanceof Product) {
                continue;
            }

            $dependencyCounts = self::dependencyCounts($record);
            $cartItems += $dependencyCounts['cart_items'];

            if ($dependencyCounts['order_items'] > 0) {
                $productsWithOrderHistory++;
            }
        }

        if ($productsWithOrderHistory > 0) {
            return "{$cartItems} cart item(s) will be removed from products that can be deleted. {$productsWithOrderHistory} selected product(s) have order history; hard deletion is disabled for them and Archive should be used.";
        }

        return "{$cartItems} cart item(s) will be removed. None of the selected products have order history.";
    }

}
