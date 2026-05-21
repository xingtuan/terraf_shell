<?php

namespace App\Filament\Resources\ProductVariants;

use App\Filament\Resources\ProductVariants\Pages\CreateProductVariant;
use App\Filament\Resources\ProductVariants\Pages\EditProductVariant;
use App\Filament\Resources\ProductVariants\Pages\ListProductVariants;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\AdminUploadStorage;
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

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.sections.variant'))
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Select::make('product_id')
                                ->label(__('admin.fields.product'))
                                ->options(fn (): array => Product::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('sku')
                                ->label(__('admin.fields.sku'))
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                            TextInput::make('title')
                                ->label(__('admin.ui.title'))
                                ->maxLength(255),
                            KeyValue::make('option_values')
                                ->label(__('admin.ui.option_values'))
                                ->keyLabel(__('admin.ui.attribute_key'))
                                ->valueLabel(__('admin.ui.value'))
                                ->columnSpanFull(),
                            TextInput::make('price_amount')
                                ->label(__('admin.labels.currency_field', ['field' => __('admin.fields.price'), 'currency' => __('admin.currency.nzd')]))
                                ->numeric()
                                ->prefix('$')
                                ->required(),
                            TextInput::make('compare_at_price_amount')
                                ->label(__('admin.ui.compare_at_price_nzd'))
                                ->numeric()
                                ->prefix('$'),
                            Hidden::make('currency')
                                ->default('NZD'),
                            TextInput::make('stock_quantity')
                                ->label(__('admin.ui.stock_quantity'))
                                ->numeric()
                                ->minValue(0),
                            Select::make('stock_status')
                                ->label(__('admin.fields.stock_status'))
                                ->options(ProductVariant::STOCK_STATUS_OPTIONS)
                                ->default('in_stock')
                                ->required(),
                            Select::make('inventory_policy')
                                ->label(__('admin.ui.inventory_policy'))
                                ->options(ProductVariant::INVENTORY_POLICY_OPTIONS)
                                ->default('deny')
                                ->required(),
                            TextInput::make('low_stock_threshold')
                                ->label(__('admin.ui.low_stock_threshold'))
                                ->numeric()
                                ->default(5)
                                ->minValue(0),
                            TextInput::make('weight_grams')
                                ->label(__('admin.fields.weight_grams'))
                                ->numeric()
                                ->minValue(0),
                            KeyValue::make('dimensions')
                                ->label(__('admin.ui.dimensions'))
                                ->keyLabel(__('admin.ui.key'))
                                ->valueLabel(__('admin.ui.value'))
                                ->columnSpanFull(),
                            TextInput::make('image_url')
                                ->label(__('admin.ui.image_url'))
                                ->placeholder('https://example.com/image.jpg')
                                ->helperText('Use only full external http/https URLs. For local/admin uploaded images, use the upload field.')
                                ->type('text')
                                ->rules(['nullable', 'url'])
                                ->maxLength(2048),
                            FileUpload::make('media_path')
                                ->label(__('admin.ui.image'))
                                ->image()
                                ->disk(fn (): string => AdminUploadStorage::disk())
                                ->directory('cms/products/variants')
                                ->visibility(fn (): string => AdminUploadStorage::visibility()),
                            Toggle::make('is_default')
                                ->label(__('admin.fields.is_default')),
                            Toggle::make('is_active')
                                ->label(__('admin.account_status.active'))
                                ->default(true),
                            TextInput::make('sort_order')
                                ->label(__('admin.ui.sort_order'))
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
                    ->label(__('admin.fields.product'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label(__('admin.fields.sku'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('title')
                    ->label(__('admin.ui.title'))
                    ->formatStateUsing(fn (?string $state, ProductVariant $record): string => $record->displayTitle())
                    ->description(fn (ProductVariant $record): string => collect($record->option_values ?? [])->map(fn ($value, $key): string => $key.': '.$value)->implode(' | ')),
                TextColumn::make('price_amount')
                    ->label(__('admin.fields.price'))
                    ->money('NZD')
                    ->sortable(),
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
                IconColumn::make('is_active')
                    ->label(__('admin.ui.active'))
                    ->boolean(),
                IconColumn::make('is_default')
                    ->label(__('admin.fields.is_default'))
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin.account_status.active')),
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
                SelectFilter::make('category_id')
                    ->label(__('admin.resources.product_category'))
                    ->options(fn (): array => ProductCategory::query()->ordered()->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('product', fn (Builder $productQuery) => $productQuery->where('category_id', $data['value']))
                        : $query),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('setDefault')
                    ->label(__('admin.actions.set_default'))
                    ->action(fn (ProductVariant $record): bool => $record->forceFill(['is_default' => true, 'is_active' => true])->save())
                    ->visible(fn (ProductVariant $record): bool => ! $record->is_default),
                Action::make('duplicate')
                    ->label(__('admin.actions.duplicate'))
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
                        $copy->title = trim(($record->title ?: $record->displayTitle()).' '.__('admin.ui.copy_suffix'));
                        $copy->is_default = false;
                        $copy->save();
                    }),
                self::adjustStockAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label(__('admin.actions.activate'))
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    BulkAction::make('deactivate')
                        ->label(__('admin.actions.deactivate'))
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function adjustStockAction(): Action
    {
        return Action::make('adjustStock')
            ->label(__('admin.actions.adjust_stock'))
            ->form([
                TextInput::make('change_quantity')
                    ->label(__('admin.fields.change_quantity'))
                    ->numeric()
                    ->required()
                    ->helperText(__('admin.help.stock_adjustment_negative')),
                Select::make('reason')
                    ->label(__('admin.fields.reason'))
                    ->options(fn (): array => self::stockAdjustmentReasons())
                    ->default('manual_adjustment')
                    ->required(),
                TextInput::make('note')
                    ->label(__('admin.fields.note'))
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

    /**
     * @return array<string, string>
     */
    private static function stockAdjustmentReasons(): array
    {
        return collect(['manual_adjustment', 'stock_received', 'damage', 'correction'])
            ->mapWithKeys(fn (string $reason): array => [$reason => __("admin.products.stock_adjustment_reason.{$reason}")])
            ->all();
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
