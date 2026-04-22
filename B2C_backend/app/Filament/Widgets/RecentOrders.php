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
use Illuminate\Database\Eloquent\Builder;

class RecentOrders extends TableWidget
{
    protected static ?string $heading = 'Fulfilment Backlog';

    protected int|string|array $columnSpan = 'full';

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
                    ->label('Order')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->description(fn (Order $record): string => $record->user?->email ?? 'Guest checkout')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus|string|null $state): string => $state instanceof OrderStatus ? $state->label() : (OrderStatus::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->color(fn (OrderStatus|string|null $state): string => $state instanceof OrderStatus ? $state->color() : (OrderStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn (OrderPaymentStatus|string|null $state): string => $state instanceof OrderPaymentStatus ? $state->label() : (OrderPaymentStatus::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->color(fn (OrderPaymentStatus|string|null $state): string => $state instanceof OrderPaymentStatus ? $state->color() : (OrderPaymentStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                TextColumn::make('total_usd')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
                TextColumn::make('shipping_country')
                    ->label('Ship to')
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Placed')
                    ->dateTime()
                    ->since(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([8])
            ->emptyStateHeading('No active fulfilment backlog.')
            ->emptyStateDescription('New orders that need review will appear here.');
    }

    public static function canView(): bool
    {
        return PanelAccess::isAdmin();
    }
}
