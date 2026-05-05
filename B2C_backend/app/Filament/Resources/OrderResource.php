<?php

namespace App\Filament\Resources;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\Users\UserResource as UserFilamentResource;
use App\Filament\Support\PanelAccess;
use App\Models\Order;
use App\Services\OrderService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|\UnitEnum|null $navigationGroup = 'Store';

    protected static ?string $navigationLabel = 'Order Requests';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Request Review')
                    ->schema([
                        Placeholder::make('order_number')
                            ->label('Order Request Number')
                            ->content(fn (Order $record): string => $record->order_number),
                        Placeholder::make('payment_method')
                            ->label('Payment Handling')
                            ->content(fn (Order $record): string => $record->payment_method ?: 'Manual follow-up'),
                        Placeholder::make('payment_reference')
                            ->label('Manual Payment Reference')
                            ->content(fn (Order $record): string => $record->payment_reference ?: 'Not captured'),
                        Placeholder::make('customer_note_summary')
                            ->label('Customer Note')
                            ->content(fn (Order $record): string => $record->customer_note ?: 'No customer note.')
                            ->columnSpanFull(),
                        Select::make('status')
                            ->options(OrderStatus::options())
                            ->required(),
                        Select::make('payment_status')
                            ->options(OrderPaymentStatus::options())
                            ->required(),
                        Textarea::make('admin_note')
                            ->rows(6)
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                InfolistSection::make('Order Request Info')
                    ->schema([
                        TextEntry::make('order_number')
                            ->copyable(),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (OrderStatus|string|null $state): string => $state instanceof OrderStatus ? $state->label() : (OrderStatus::tryFrom((string) $state)?->label() ?? (string) $state))
                            ->color(fn (OrderStatus|string|null $state): string => $state instanceof OrderStatus ? $state->color() : (OrderStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                        TextEntry::make('payment_status')
                            ->badge()
                            ->formatStateUsing(fn (OrderPaymentStatus|string|null $state): string => $state instanceof OrderPaymentStatus ? $state->label() : (OrderPaymentStatus::tryFrom((string) $state)?->label() ?? (string) $state))
                            ->color(fn (OrderPaymentStatus|string|null $state): string => $state instanceof OrderPaymentStatus ? $state->color() : (OrderPaymentStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
                InfolistSection::make('Customer')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Name')
                            ->url(fn (Order $record): ?string => $record->user ? UserFilamentResource::getUrl('view', ['record' => $record->user]) : null)
                            ->placeholder('-'),
                        TextEntry::make('user.email')
                            ->label('Email')
                            ->copyable()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                InfolistSection::make('Shipping Address')
                    ->schema([
                        TextEntry::make('shipping_name'),
                        TextEntry::make('shipping_phone')
                            ->placeholder('-'),
                        TextEntry::make('shipping_address_line1'),
                        TextEntry::make('shipping_address_line2')
                            ->placeholder('-'),
                        TextEntry::make('shipping_city'),
                        TextEntry::make('shipping_state_province')
                            ->placeholder('-'),
                        TextEntry::make('shipping_postal_code')
                            ->placeholder('-'),
                        TextEntry::make('shipping_country'),
                    ])
                    ->columns(2),
                InfolistSection::make('Manual Payment')
                    ->schema([
                        TextEntry::make('payment_method')
                            ->placeholder('-'),
                        TextEntry::make('payment_reference')
                            ->placeholder('-')
                            ->copyable(),
                    ])
                    ->columns(2),
                InfolistSection::make('Order Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('product_name')
                                    ->label('Product'),
                                TextEntry::make('quantity'),
                                TextEntry::make('unit_price_usd')
                                    ->label('Unit Price')
                                    ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
                                TextEntry::make('subtotal_usd')
                                    ->label('Subtotal')
                                    ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
                            ])
                            ->columns(4),
                    ]),
                InfolistSection::make('Totals')
                    ->schema([
                        TextEntry::make('subtotal_usd')
                            ->label('Subtotal')
                            ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
                        TextEntry::make('shipping_usd')
                            ->label('Shipping')
                            ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
                        TextEntry::make('total_usd')
                            ->label('Total')
                            ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
                    ])
                    ->columns(3),
                InfolistSection::make('Admin Note')
                    ->schema([
                        TextEntry::make('customer_note')
                            ->label('Customer note')
                            ->placeholder('-'),
                        TextEntry::make('admin_note')
                            ->label('Internal note')
                            ->placeholder('-'),
                    ]),
                InfolistSection::make('Timeline')
                    ->schema([
                        TextEntry::make('confirmed_at')
                            ->label('Confirmed')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('shipped_at')
                            ->label('Shipped')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('delivered_at')
                            ->label('Delivered')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('cancelled_at')
                            ->label('Cancelled')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user'])->withCount('items'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order request')
                    ->searchable()
                    ->copyable()
                    ->description(fn (Order $record): string => collect([
                        $record->payment_method ?: null,
                        $record->payment_reference ?: null,
                    ])->filter()->implode(' | ')),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (Order $record): string => collect([
                        $record->user?->email,
                        $record->shipping_country,
                    ])->filter()->implode(' | ') ?: '-'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus|string|null $state): string => $state instanceof OrderStatus ? $state->label() : (OrderStatus::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->color(fn (OrderStatus|string|null $state): string => $state instanceof OrderStatus ? $state->color() : (OrderStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                TextColumn::make('payment_status')
                    ->label('Manual payment status')
                    ->badge()
                    ->formatStateUsing(fn (OrderPaymentStatus|string|null $state): string => $state instanceof OrderPaymentStatus ? $state->label() : (OrderPaymentStatus::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->color(fn (OrderPaymentStatus|string|null $state): string => $state instanceof OrderPaymentStatus ? $state->color() : (OrderPaymentStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                TextColumn::make('total_usd')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->numeric(),
                TextColumn::make('shipping_country')
                    ->label('Ship to')
                    ->toggleable(),
                TextColumn::make('customer_note')
                    ->label('Customer note')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('open_fulfilment')
                    ->label('Open requests')
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', [
                        OrderStatus::Pending->value,
                        OrderStatus::Confirmed->value,
                        OrderStatus::Processing->value,
                        OrderStatus::Shipped->value,
                    ])),
                SelectFilter::make('status')
                    ->options(OrderStatus::options()),
                SelectFilter::make('payment_status')
                    ->options(OrderPaymentStatus::options()),
                SelectFilter::make('shipping_country')
                    ->label('Ship to country')
                    ->options(fn (): array => Order::query()
                        ->whereNotNull('shipping_country')
                        ->select('shipping_country')
                        ->distinct()
                        ->orderBy('shipping_country')
                        ->pluck('shipping_country', 'shipping_country')
                        ->all()),
                SelectFilter::make('payment_method')
                    ->options(fn (): array => Order::query()
                        ->whereNotNull('payment_method')
                        ->select('payment_method')
                        ->distinct()
                        ->orderBy('payment_method')
                        ->pluck('payment_method', 'payment_method')
                        ->all()),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Created from'),
                        DatePicker::make('created_until')
                            ->label('Created until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $builder, string $date): Builder => $builder->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $builder, string $date): Builder => $builder->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('changeStatus')
                    ->label('Update request status')
                    ->form([
                        Select::make('status')
                            ->options([
                                OrderStatus::Confirmed->value => OrderStatus::Confirmed->label(),
                                OrderStatus::Processing->value => OrderStatus::Processing->label(),
                                OrderStatus::Shipped->value => OrderStatus::Shipped->label(),
                                OrderStatus::Delivered->value => OrderStatus::Delivered->label(),
                                OrderStatus::Cancelled->value => OrderStatus::Cancelled->label(),
                            ])
                            ->required(),
                    ])
                    ->fillForm(fn (Order $record): array => [
                        'status' => $record->status instanceof OrderStatus ? $record->status->value : (string) $record->status,
                    ])
                    ->action(function (Order $record, array $data): void {
                        $status = OrderStatus::from($data['status']);
                        $payload = ['status' => $status];

                        if ($status === OrderStatus::Confirmed && $record->confirmed_at === null) {
                            $payload['confirmed_at'] = now();
                        }

                        if ($status === OrderStatus::Shipped && $record->shipped_at === null) {
                            $payload['shipped_at'] = now();
                        }

                        if ($status === OrderStatus::Delivered && $record->delivered_at === null) {
                            $payload['delivered_at'] = now();
                        }

                        if ($status === OrderStatus::Cancelled && $record->cancelled_at === null) {
                            $payload['cancelled_at'] = now();
                        }

                        $previousStatus = $record->status instanceof OrderStatus ? $record->status->value : (string) $record->status;

                        $record->forceFill($payload)->save();

                        if ($previousStatus !== $status->value) {
                            app(OrderService::class)->dispatchStatusChangedEmail($record->fresh(['user', 'items.product']), $previousStatus);
                        }

                        Notification::make()
                            ->title('Order request status updated.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'items.product']);
    }

    public static function canViewAny(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canView(Model $record): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->whereIn('status', [
                OrderStatus::Pending->value,
                OrderStatus::Confirmed->value,
                OrderStatus::Processing->value,
                OrderStatus::Shipped->value,
            ])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
