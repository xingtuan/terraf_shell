<?php

namespace App\Filament\Resources\ProductVariants;

use App\Filament\Resources\ProductVariants\Pages\CreateProductVariant;
use App\Filament\Resources\ProductVariants\Pages\EditProductVariant;
use App\Filament\Resources\ProductVariants\Pages\ListProductVariants;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\HasAdminResourceTranslations;
use App\Filament\Support\PanelAccess;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductVariantResource extends Resource
{
    use HasAdminResourceTranslations;

    protected static ?string $model = ProductVariant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::StoreOperations;

    protected static ?string $navigationLabel = 'Product Variants';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Variant')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->options(fn (): array => Product::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('sku')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                            TextInput::make('title')
                                ->maxLength(255),
                            KeyValue::make('option_values')
                                ->columnSpanFull(),
                            TextInput::make('price_amount')
                                ->label('Price (NZD)')
                                ->numeric()
                                ->prefix('$')
                                ->required(),
                            TextInput::make('compare_at_price_amount')
                                ->label('Compare-at price (NZD)')
                                ->numeric()
                                ->prefix('$'),
                            Hidden::make('currency')
                                ->default('NZD'),
                            TextInput::make('stock_quantity')
                                ->numeric()
                                ->minValue(0),
                            Select::make('stock_status')
                                ->options(ProductVariant::STOCK_STATUS_OPTIONS)
                                ->default('in_stock')
                                ->required(),
                            Select::make('inventory_policy')
                                ->options(ProductVariant::INVENTORY_POLICY_OPTIONS)
                                ->default('deny')
                                ->required(),
                            TextInput::make('low_stock_threshold')
                                ->numeric()
                                ->default(5)
                                ->minValue(0),
                            TextInput::make('weight_grams')
                                ->numeric()
                                ->minValue(0),
                            KeyValue::make('dimensions')
                                ->columnSpanFull(),
                            TextInput::make('image_url')
                                ->url()
                                ->maxLength(2048),
                            FileUpload::make('media_path')
                                ->image()
                                ->disk((string) config('community.uploads.disk'))
                                ->directory('cms/products/variants')
                                ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public'),
                            Toggle::make('is_default')
                                ->label('Default variant'),
                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                            TextInput::make('sort_order')
                                ->numeric()
                                ->default(0),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['product.category'])->ordered())
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('title')
                    ->formatStateUsing(fn (?string $state, ProductVariant $record): string => $record->displayTitle())
                    ->description(fn (ProductVariant $record): string => collect($record->option_values ?? [])->map(fn ($value, $key): string => $key.': '.$value)->implode(' | ')),
                TextColumn::make('price_amount')
                    ->label('Price')
                    ->money('NZD')
                    ->sortable(),
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
                IconColumn::make('is_active')
                    ->boolean(),
                IconColumn::make('is_default')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
                SelectFilter::make('stock_status')
                    ->options(ProductVariant::STOCK_STATUS_OPTIONS),
                SelectFilter::make('inventory_policy')
                    ->options(ProductVariant::INVENTORY_POLICY_OPTIONS),
                Filter::make('low_stock')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('stock_quantity')
                        ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')),
                SelectFilter::make('category_id')
                    ->label('Product category')
                    ->options(fn (): array => ProductCategory::query()->ordered()->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('product', fn (Builder $productQuery) => $productQuery->where('category_id', $data['value']))
                        : $query),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('setDefault')
                    ->label('Set as default')
                    ->action(fn (ProductVariant $record): bool => $record->forceFill(['is_default' => true, 'is_active' => true])->save())
                    ->visible(fn (ProductVariant $record): bool => ! $record->is_default),
                Action::make('duplicate')
                    ->label('Duplicate')
                    ->action(function (ProductVariant $record): void {
                        $copy = $record->replicate(['sku', 'is_default']);
                        $baseSku = $record->sku.'_COPY';
                        $candidate = $baseSku;
                        $suffix = 2;

                        while (ProductVariant::query()->where('sku', $candidate)->exists()) {
                            $candidate = $baseSku.'_'.$suffix;
                            $suffix++;
                        }

                        $copy->sku = $candidate;
                        $copy->title = trim(($record->title ?: $record->displayTitle()).' Copy');
                        $copy->is_default = false;
                        $copy->save();
                    }),
                self::adjustStockAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    BulkAction::make('deactivate')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function adjustStockAction(): Action
    {
        return Action::make('adjustStock')
            ->label('Adjust stock')
            ->form([
                TextInput::make('change_quantity')
                    ->label('Change quantity')
                    ->numeric()
                    ->required()
                    ->helperText('Use a negative number to reduce stock.'),
                Select::make('reason')
                    ->options([
                        'manual_adjustment' => 'Manual adjustment',
                        'stock_received' => 'Stock received',
                        'damage' => 'Damage',
                        'correction' => 'Correction',
                    ])
                    ->default('manual_adjustment')
                    ->required(),
                TextInput::make('note')
                    ->maxLength(255),
            ])
            ->action(fn (ProductVariant $record, array $data) => $record->adjustStock(
                (int) $data['change_quantity'],
                (string) $data['reason'],
                $data['note'] ?? null,
                auth()->id(),
            ));
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
        return PanelAccess::isAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductVariants::route('/'),
            'create' => CreateProductVariant::route('/create'),
            'edit' => EditProductVariant::route('/{record}/edit'),
        ];
    }
}
