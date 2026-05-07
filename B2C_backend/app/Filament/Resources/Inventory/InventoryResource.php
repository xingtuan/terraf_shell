<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\Pages\EditInventoryVariant;
use App\Filament\Resources\Inventory\Pages\ListInventory;
use App\Filament\Resources\ProductVariants\ProductVariantResource as VariantActions;
use App\Filament\Support\PanelAccess;
use App\Models\ProductVariant;
use BackedEnum;
use Filament\Actions\EditAction;
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
    protected static ?string $model = ProductVariant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Shop';

    protected static ?string $navigationLabel = 'Inventory';

    protected static ?int $navigationSort = 22;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Inventory')
                ->schema([
                    \Filament\Forms\Components\TextInput::make('stock_quantity')
                        ->numeric()
                        ->minValue(0),
                    \Filament\Forms\Components\Select::make('stock_status')
                        ->options(ProductVariant::STOCK_STATUS_OPTIONS)
                        ->required(),
                    \Filament\Forms\Components\Select::make('inventory_policy')
                        ->options(ProductVariant::INVENTORY_POLICY_OPTIONS)
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('low_stock_threshold')
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
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable()
                    ->placeholder('Untracked'),
                TextColumn::make('stock_status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ProductVariant::STOCK_STATUS_OPTIONS[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'low_stock' => 'warning',
                        'preorder', 'made_to_order' => 'info',
                        'sold_out' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('inventory_policy')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ProductVariant::INVENTORY_POLICY_OPTIONS[$state] ?? (string) $state),
                TextColumn::make('inventoryAdjustments.reason')
                    ->label('Recent adjustments')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('stock_status')
                    ->options(ProductVariant::STOCK_STATUS_OPTIONS),
                SelectFilter::make('inventory_policy')
                    ->options(ProductVariant::INVENTORY_POLICY_OPTIONS),
                Filter::make('low_stock')
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
