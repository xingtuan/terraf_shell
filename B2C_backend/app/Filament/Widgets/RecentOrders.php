<?php

namespace App\Filament\Widgets;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use App\Filament\Support\PanelAccess;
use App\Models\Order;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class RecentOrders extends TableWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): string|Htmlable|null
    {
        return __('admin.widgets.order_backlog');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Order::query()
                ->with('user')
                ->withCount('items')
                ->whereIn('status', [
                    OrderStatus::Pending->value,
                    OrderStatus::Confirmed->value,
                    OrderStatus::Processing->value,
                    OrderStatus::Shipped->value,
                ])
                ->latest())
            ->columns([
                TextColumn::make('order_number')
                    ->label(__('admin.fields.order_number'))
                    ->copyable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label(__('admin.fields.customer'))
                    ->description(fn (Order $record): string => $record->user?->email ?? 'Customer email unavailable')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus|string|null $state): string => $state instanceof OrderStatus ? $state->label() : (OrderStatus::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->color(fn (OrderStatus|string|null $state): string => $state instanceof OrderStatus ? $state->color() : (OrderStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                TextColumn::make('payment_status')
                    ->label(__('admin.fields.payment_status'))
                    ->badge()
                    ->formatStateUsing(fn (OrderPaymentStatus|string|null $state): string => $state instanceof OrderPaymentStatus ? $state->label() : (OrderPaymentStatus::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->color(fn (OrderPaymentStatus|string|null $state): string => $state instanceof OrderPaymentStatus ? $state->color() : (OrderPaymentStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                TextColumn::make('total_usd')
                    ->label(__('admin.fields.total'))
                    ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
                TextColumn::make('shipping_country')
                    ->label(__('admin.fields.shipping'))
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->since(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([8])
            ->emptyStateHeading(__('admin.ui.no_active_order_request_backlog'))
            ->emptyStateDescription(__('admin.ui.new_order_requests_that_need_review_will_appear_here'));
    }

    public static function canView(): bool
    {
        return PanelAccess::isAdmin();
    }
}
