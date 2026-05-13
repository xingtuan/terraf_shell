<?php

namespace App\Filament\Widgets;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\ProductCategories\ProductCategoryResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\PanelAccess;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = null;

    protected ?string $description = null;

    protected function getHeading(): ?string
    {
        return __('admin.widgets.store_overview');
    }

    protected function getDescription(): ?string
    {
        return __('admin.ui.catalogue_health_description');
    }

    protected function getStats(): array
    {
        $orderBacklog = Order::query()
            ->whereIn('status', [
                OrderStatus::Pending->value,
                OrderStatus::Confirmed->value,
                OrderStatus::Processing->value,
                OrderStatus::Shipped->value,
            ])
            ->count();

        $liveProducts = Product::query()
            ->where('status', ProductStatus::Published->value)
            ->where('is_active', true)
            ->count();

        $stockAlerts = ProductVariant::query()
            ->whereIn('stock_status', ['low_stock', 'sold_out'])
            ->where('is_active', true)
            ->whereHas('product', fn ($query) => $query->where('status', ProductStatus::Published->value))
            ->distinct()
            ->count('product_id');

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->count();

        return [
            Stat::make(__('admin.ui.order_request_backlog'), number_format($orderBacklog))
                ->description(__('admin.ui.pending_review_confirmed_processing_or_shipped_requests'))
                ->color($orderBacklog > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-shopping-bag')
                ->url(OrderResource::getUrl()),
            Stat::make(__('admin.ui.published_products'), number_format($liveProducts))
                ->description(__('admin.ui.active_catalogue_records_visible_to_the_storefront'))
                ->color('success')
                ->icon('heroicon-o-cube')
                ->url(ProductResource::getUrl()),
            Stat::make(__('admin.ui.stock_alerts'), number_format($stockAlerts))
                ->description(__('admin.ui.products_marked_low_stock_or_sold_out'))
                ->color($stockAlerts > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle')
                ->url(ProductResource::getUrl()),
            Stat::make(__('admin.ui.active_categories'), number_format($categories))
                ->description(__('admin.ui.storefront_taxonomy_groups_in_use'))
                ->color('info')
                ->icon('heroicon-o-squares-2x2')
                ->url(ProductCategoryResource::getUrl()),
            Stat::make(
                __('admin.ui.manual_payment_backlog'),
                number_format(
                    Order::query()
                        ->where('payment_status', OrderPaymentStatus::Unpaid->value)
                        ->whereNotIn('status', [OrderStatus::Cancelled->value])
                        ->count(),
                ),
            )
                ->description(__('admin.ui.order_requests_awaiting_manual_payment_instructions_or_confirmation'))
                ->color('warning')
                ->icon('heroicon-o-credit-card')
                ->url(OrderResource::getUrl()),
            Stat::make(
                __('admin.ui.inquiry_only_products'),
                number_format(
                    Product::query()
                        ->where('inquiry_only', true)
                        ->where('status', ProductStatus::Published->value)
                        ->count(),
                ),
            )
                ->description(__('admin.ui.catalogue_items_routed_to_contact_led_conversion'))
                ->color('gray')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->url(ProductResource::getUrl()),
        ];
    }

    public static function canView(): bool
    {
        return PanelAccess::isAdmin();
    }
}
