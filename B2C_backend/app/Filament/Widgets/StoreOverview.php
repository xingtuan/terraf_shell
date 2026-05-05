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
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Store Overview';

    protected ?string $description = 'Catalogue health and fulfilment workload for the storefront.';

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

        $stockAlerts = Product::query()
            ->whereIn('stock_status', ['low_stock', 'sold_out'])
            ->where('status', ProductStatus::Published->value)
            ->count();

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->count();

        return [
            Stat::make('Order request backlog', number_format($orderBacklog))
                ->description('Pending review, confirmed, processing, or shipped requests')
                ->color($orderBacklog > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-shopping-bag')
                ->url(OrderResource::getUrl()),
            Stat::make('Published products', number_format($liveProducts))
                ->description('Active catalogue records visible to the storefront')
                ->color('success')
                ->icon('heroicon-o-cube')
                ->url(ProductResource::getUrl()),
            Stat::make('Stock alerts', number_format($stockAlerts))
                ->description('Products marked low stock or sold out')
                ->color($stockAlerts > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle')
                ->url(ProductResource::getUrl()),
            Stat::make('Active categories', number_format($categories))
                ->description('Storefront taxonomy groups in use')
                ->color('info')
                ->icon('heroicon-o-squares-2x2')
                ->url(ProductCategoryResource::getUrl()),
            Stat::make(
                'Manual payment backlog',
                number_format(
                    Order::query()
                        ->where('payment_status', OrderPaymentStatus::Unpaid->value)
                        ->whereNotIn('status', [OrderStatus::Cancelled->value])
                        ->count(),
                ),
            )
                ->description('Order requests awaiting manual payment instructions or confirmation')
                ->color('warning')
                ->icon('heroicon-o-credit-card')
                ->url(OrderResource::getUrl()),
            Stat::make(
                'Inquiry-only products',
                number_format(
                    Product::query()
                        ->where('inquiry_only', true)
                        ->where('status', ProductStatus::Published->value)
                        ->count(),
                ),
            )
                ->description('Catalogue items routed to contact-led conversion')
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
