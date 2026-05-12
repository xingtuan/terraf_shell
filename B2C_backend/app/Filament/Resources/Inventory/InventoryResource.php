<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\Pages\EditInventoryVariant;
use App\Filament\Resources\Inventory\Pages\ListInventory;
use App\Filament\Resources\ProductVariants\ProductVariantResource as VariantActions;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\HasAdminResourceTranslations;
use App\Filament\Support\PanelAccess;
use App\Models\ProductVariant;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventoryResource extends Resource
{
    use HasAdminResourceTranslations;

    protected static ?string $model = ProductVariant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::StoreOperations;

    protected static ?int $navigationSort = 22;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.sections.inventory'))
                ->schema([
                    TextInput::make('stock_quantity')
                        ->label(__('admin.ui.stock_quantity'))
                        ->numeric()
                        ->minValue(0),
                    Select::make('stock_status')
                        ->label(__('admin.fields.stock_status'))
                        ->options(fn (): array => self::stockStatusOptions())
                        ->required(),
                    Select::make('inventory_policy')
                        ->label(__('admin.ui.inventory_policy'))
                        ->options(fn (): array => self::inventoryPolicyOptions())
                        ->required(),
                    TextInput::make('low_stock_threshold')
                        ->label(__('admin.ui.low_stock_threshold'))
                        ->numeric()
                        ->minValue(0),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['product.category', 'inventoryAdjustments' => fn ($adjustments) => $adjustments->latest()->limit(3)])
                ->ordered())
            ->columns([
                TextColumn::make('product.name')
                    ->label(__('admin.fields.product'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label(__('admin.fields.sku'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('stock_quantity')
                    ->label(__('admin.fields.stock'))
                    ->sortable()
                    ->placeholder(__('admin.placeholders.untracked')),
                TextColumn::make('stock_status')
                    ->label(__('admin.fields.stock_status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? __("admin.products.stock_status.{$state}") : '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'low_stock' => 'warning',
                        'preorder', 'made_to_order' => 'info',
                        'sold_out' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('inventory_policy')
                    ->label(__('admin.ui.inventory_policy'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? __("admin.products.inventory_policy.{$state}") : '-'),
                TextColumn::make('inventoryAdjustments.reason')
                    ->label(__('admin.fields.reason'))
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('admin.ui.active'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('stock_status')
                    ->label(__('admin.fields.stock_status'))
                    ->options(fn (): array => self::stockStatusOptions()),
                SelectFilter::make('inventory_policy')
                    ->label(__('admin.ui.inventory_policy'))
                    ->options(fn (): array => self::inventoryPolicyOptions()),
                Filter::make('low_stock')
                    ->label(__('admin.filters.low_stock'))
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('stock_quantity')
                        ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')),
            ])
            ->recordActions([
                EditAction::make(),
                VariantActions::adjustStockAction(),
            ]);
    }

    public static function canViewAny(): bool
    {
        return PanelAccess::isAdmin();
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

    /**
     * @return array<string, string>
     */
    private static function inventoryPolicyOptions(): array
    {
        return collect(array_keys(ProductVariant::INVENTORY_POLICY_OPTIONS))
            ->mapWithKeys(fn (string $policy): array => [$policy => __("admin.products.inventory_policy.{$policy}")])
            ->all();
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

    public static function getPages(): array
    {
        return [
            'index' => ListInventory::route('/'),
            'edit' => EditInventoryVariant::route('/{record}/edit'),
        ];
    }
}
