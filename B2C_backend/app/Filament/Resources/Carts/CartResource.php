<?php

namespace App\Filament\Resources\Carts;

use App\Filament\Resources\Carts\Pages\ListCarts;
use App\Filament\Resources\Carts\Pages\ViewCart;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\HasAdminResourceTranslations;
use App\Filament\Support\PanelAccess;
use App\Models\Cart;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CartResource extends Resource
{
    use HasAdminResourceTranslations;

    protected static ?string $model = Cart::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::StoreOperations;

    protected static ?int $navigationSort = 60;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'items.product', 'items.variant']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label(__('admin.ui.id'))
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('admin.fields.customer'))
                    ->placeholder(__('admin.placeholders.guest_session_cart'))
                    ->searchable()
                    ->description(fn (Cart $record): string => $record->user?->email ?: ($record->session_key ? __('admin.labels.field_value', ['field' => __('admin.fields.session_key'), 'value' => $record->session_key]) : '-')),
                TextColumn::make('item_count')
                    ->label(__('admin.fields.item_count'))
                    ->state(fn (Cart $record): int => $record->itemCount())
                    ->numeric(),
                TextColumn::make('estimated_total')
                    ->label(__('admin.fields.estimated_total'))
                    ->state(fn (Cart $record): string => '$'.$record->total().' NZD'),
                TextColumn::make('expires_at')
                    ->label(__('admin.fields.expires_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('admin.fields.last_activity'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('abandoned')
                    ->label(__('admin.filters.abandoned_carts'))
                    ->query(fn (Builder $query): Builder => $query
                        ->where('updated_at', '<=', now()->subDays(7))
                        ->whereHas('items')),
                Filter::make('guest')
                    ->label(__('admin.filters.guest_carts'))
                    ->query(fn (Builder $query): Builder => $query->whereNull('user_id')),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.cart'))
                    ->schema([
                        TextEntry::make('id')
                            ->label(__('admin.ui.id')),
                        TextEntry::make('user.name')
                            ->label(__('admin.fields.customer'))
                            ->placeholder(__('admin.placeholders.guest_session_cart')),
                        TextEntry::make('user.email')
                            ->label(__('admin.fields.email'))
                            ->placeholder('-')
                            ->copyable(),
                        TextEntry::make('session_key')
                            ->label(__('admin.fields.session_key'))
                            ->copyable()
                            ->placeholder('-'),
                        TextEntry::make('expires_at')
                            ->label(__('admin.fields.expires_at'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label(__('admin.fields.last_activity'))
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.items'))
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label(__('admin.fields.product')),
                                TextEntry::make('variant.sku')
                                    ->label(__('admin.fields.sku'))
                                    ->placeholder(fn ($record): ?string => $record->product?->effectiveSku()),
                                TextEntry::make('quantity'),
                                TextEntry::make('unit_price_amount')
                                    ->label(__('admin.fields.unit_price'))
                                    ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2).' NZD'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canView(Model $record): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
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

    public static function getPages(): array
    {
        return [
            'index' => ListCarts::route('/'),
            'view' => ViewCart::route('/{record}'),
        ];
    }
}
