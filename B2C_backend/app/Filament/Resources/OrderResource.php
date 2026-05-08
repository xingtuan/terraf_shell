<?php

namespace App\Filament\Resources;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\Users\UserResource as UserFilamentResource;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\HasAdminResourceTranslations;
use App\Filament\Support\PanelAccess;
use App\Models\Order;
use App\Services\OrderService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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
    use HasAdminResourceTranslations;

    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::StoreOperations;

    protected static ?string $navigationLabel = 'Orders';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.order_review'))
                    ->schema([
                        Placeholder::make('order_number')
                            ->label(__('admin.fields.order_number'))
                            ->content(fn (Order $record): string => $record->order_number),
                        Placeholder::make('payment_method')
                            ->label(__('admin.fields.payment_method'))
                            ->content(fn (Order $record): string => $record->payment_method ?: __('admin.placeholders.manual_follow_up')),
                        Placeholder::make('payment_reference')
                            ->label(__('admin.fields.payment_reference'))
                            ->content(fn (Order $record): string => $record->payment_reference ?: __('admin.placeholders.not_captured')),
                        Placeholder::make('customer_note_summary')
                            ->label(__('admin.fields.customer_note'))
                            ->content(fn (Order $record): string => $record->customer_note ?: __('admin.placeholders.no_customer_note'))
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
                Section::make(__('admin.sections.order_info'))
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
                Section::make(__('admin.sections.customer'))
                    ->schema([
                        TextEntry::make('user.name')
                            ->label(__('admin.fields.name'))
                            ->url(fn (Order $record): ?string => $record->user ? UserFilamentResource::getUrl('view', ['record' => $record->user]) : null)
                            ->placeholder('-'),
                        TextEntry::make('user.email')
                            ->label(__('admin.fields.email'))
                            ->copyable()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.shipping_address'))
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
                Section::make(__('admin.sections.manual_payment'))
                    ->schema([
                        TextEntry::make('payment_method')
                            ->placeholder('-'),
                        TextEntry::make('payment_reference')
                            ->placeholder('-')
                            ->copyable(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.order_items'))
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('product_name')
                                    ->label(__('admin.fields.product')),
                                TextEntry::make('variant_title')
                                    ->label(__('admin.fields.variant'))
                                    ->placeholder('-'),
                                TextEntry::make('variant_sku')
                                    ->label(__('admin.fields.sku'))
                                    ->copyable()
                                    ->placeholder('-'),
                                TextEntry::make('quantity'),
                                TextEntry::make('unit_price_usd')
                                    ->label(__('admin.fields.unit_price').' (NZD)')
                                    ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
                                TextEntry::make('subtotal_usd')
                                    ->label(__('admin.fields.subtotal').' (NZD)')
                                    ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
                            ])
                            ->columns(6),
                    ]),
                Section::make(__('admin.sections.totals'))
                    ->schema([
                        TextEntry::make('subtotal_usd')
                            ->label(__('admin.fields.subtotal').' (NZD)')
                            ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
                        TextEntry::make('shipping_usd')
                            ->label(__('admin.fields.shipping').' (NZD)')
                            ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
                        TextEntry::make('total_usd')
                            ->label(__('admin.fields.total').' (NZD)')
                            ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
                    ])
                    ->columns(3),
                Section::make(__('admin.sections.admin_note'))
                    ->schema([
                        TextEntry::make('customer_note')
                            ->label(__('admin.fields.customer_note'))
                            ->placeholder('-'),
                        TextEntry::make('admin_note')
                            ->label(__('admin.fields.admin_note'))
                            ->placeholder('-'),
                    ]),
                Section::make(__('admin.sections.timeline'))
                    ->schema([
                        TextEntry::make('confirmed_at')
                            ->label(__('admin.orders.status.confirmed'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('shipped_at')
                            ->label(__('admin.orders.status.shipped'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('delivered_at')
                            ->label(__('admin.orders.status.delivered'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('cancelled_at')
                            ->label(__('admin.orders.status.cancelled'))
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
                    ->label(__('admin.fields.order_number'))
                    ->searchable()
                    ->copyable()
                    ->description(fn (Order $record): string => collect([
                        $record->payment_method ?: null,
                        $record->payment_reference ?: null,
                    ])->filter()->implode(' | ')),
                TextColumn::make('user.name')
                    ->label(__('admin.fields.customer'))
                    ->searchable()
                    ->description(fn (Order $record): string => collect([
                        $record->user?->email ?: $record->guest_email,
                        $record->shipping_country,
                    ])->filter()->implode(' | ') ?: '-'),
                TextColumn::make('guest_email')
                    ->label(__('admin.fields.guest_email'))
                    ->copyable()
                    ->placeholder('-')
                    ->toggleable(),
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
                    ->label(__('admin.fields.total').' (NZD)')
                    ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
                TextColumn::make('items_count')
                    ->label(__('admin.sections.items'))
                    ->numeric(),
                TextColumn::make('shipping_country')
                    ->label(__('admin.fields.shipping'))
                    ->toggleable(),
                TextColumn::make('shipping_city')
                    ->label(__('admin.fields.city'))
                    ->toggleable(),
                TextColumn::make('customer_note')
                    ->label(__('admin.fields.customer_note'))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('open_fulfilment')
                    ->label(__('admin.filters.open_orders'))
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
                Filter::make('guest_or_registered')
                    ->label(__('admin.filters.guest_or_registered'))
                    ->schema([
                        Select::make('type')
                            ->options([
                                'guest' => __('admin.fields.guest'),
                                'registered' => __('admin.fields.registered'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(($data['type'] ?? null) === 'guest', fn (Builder $builder): Builder => $builder->whereNull('user_id'))
                            ->when(($data['type'] ?? null) === 'registered', fn (Builder $builder): Builder => $builder->whereNotNull('user_id'));
                    }),
                SelectFilter::make('shipping_country')
                    ->label(__('admin.fields.country'))
                    ->options(fn (): array => Order::query()
                        ->whereNotNull('shipping_country')
                        ->select('shipping_country')
                        ->distinct()
                        ->orderBy('shipping_country')
                        ->pluck('shipping_country', 'shipping_country')
                        ->all()),
                SelectFilter::make('shipping_city')
                    ->label(__('admin.fields.city'))
                    ->options(fn (): array => Order::query()
                        ->whereNotNull('shipping_city')
                        ->select('shipping_city')
                        ->distinct()
                        ->orderBy('shipping_city')
                        ->pluck('shipping_city', 'shipping_city')
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
                            ->label(__('admin.fields.created_at').' from'),
                        DatePicker::make('created_until')
                            ->label(__('admin.fields.created_at').' until'),
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
                    ->label(__('admin.actions.update_order_status'))
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
                        self::updateOrderStatus($record, OrderStatus::from($data['status']));

                        Notification::make()
                            ->title(__('admin.notifications.order_status_updated'))
                            ->success()
                            ->send();
                    }),
                Action::make('changePaymentStatus')
                    ->label(__('admin.actions.update_payment_status'))
                    ->icon('heroicon-o-credit-card')
                    ->form([
                        Select::make('payment_status')
                            ->options(OrderPaymentStatus::options())
                            ->required(),
                        Textarea::make('payment_reference')
                            ->label(__('admin.fields.payment_reference'))
                            ->rows(2)
                            ->maxLength(255),
                    ])
                    ->fillForm(fn (Order $record): array => [
                        'payment_status' => $record->payment_status instanceof OrderPaymentStatus ? $record->payment_status->value : (string) $record->payment_status,
                        'payment_reference' => $record->payment_reference,
                    ])
                    ->action(function (Order $record, array $data): void {
                        $record->forceFill([
                            'payment_status' => OrderPaymentStatus::from($data['payment_status']),
                            'payment_reference' => $data['payment_reference'] ?? $record->payment_reference,
                        ])->save();

                        Notification::make()
                            ->title(__('admin.notifications.payment_status_updated'))
                            ->success()
                            ->send();
                    }),
                Action::make('addInternalNote')
                    ->label(__('admin.actions.add_internal_note'))
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        Textarea::make('admin_note')
                            ->label(__('admin.fields.admin_note'))
                            ->rows(5)
                            ->maxLength(255)
                            ->required(),
                    ])
                    ->fillForm(fn (Order $record): array => [
                        'admin_note' => $record->admin_note,
                    ])
                    ->action(function (Order $record, array $data): void {
                        $record->forceFill(['admin_note' => $data['admin_note']])->save();

                        Notification::make()
                            ->title(__('admin.notifications.internal_note_added'))
                            ->success()
                            ->send();
                    }),
                self::statusAction('markConfirmed', __('admin.actions.mark_confirmed'), OrderStatus::Confirmed, 'info'),
                self::statusAction('markProcessing', __('admin.actions.mark_processing'), OrderStatus::Processing, 'warning'),
                self::statusAction('markShipped', __('admin.actions.mark_shipped'), OrderStatus::Shipped, 'purple'),
                self::statusAction('markDelivered', __('admin.actions.mark_delivered'), OrderStatus::Delivered, 'success'),
                self::statusAction('cancelOrder', __('admin.actions.cancel_order'), OrderStatus::Cancelled, 'danger'),
            ]);
    }

    private static function statusAction(string $name, string $label, OrderStatus $status, string $color): Action
    {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->requiresConfirmation()
            ->visible(fn (Order $record): bool => ($record->status instanceof OrderStatus ? $record->status : OrderStatus::tryFrom((string) $record->status)) !== $status)
            ->action(function (Order $record) use ($status): void {
                self::updateOrderStatus($record, $status);

                Notification::make()
                    ->title(__('admin.notifications.order_status_updated'))
                    ->success()
                    ->send();
            });
    }

    private static function updateOrderStatus(Order $record, OrderStatus $status): void
    {
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
            app(OrderService::class)->dispatchStatusChangedEmail($record->fresh(['user', 'items.product.variants', 'items.variant']), $previousStatus);
        }
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'items.product.variants', 'items.variant']);
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
