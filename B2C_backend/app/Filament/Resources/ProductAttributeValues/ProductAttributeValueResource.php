<?php

namespace App\Filament\Resources\ProductAttributeValues;

use App\Filament\Resources\ProductAttributeValues\Pages\CreateProductAttributeValue;
use App\Filament\Resources\ProductAttributeValues\Pages\EditProductAttributeValue;
use App\Filament\Resources\ProductAttributeValues\Pages\ListProductAttributeValues;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\HasAdminResourceTranslations;
use App\Filament\Support\PanelAccess;
use App\Models\ProductAttributeDefinition;
use App\Models\ProductAttributeValue;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductAttributeValueResource extends Resource
{
    use HasAdminResourceTranslations;

    protected static ?string $model = ProductAttributeValue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::StoreOperations;

    protected static ?string $navigationLabel = 'Attribute Values';

    protected static ?int $navigationSort = 41;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.ui.attribute_value'))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('attribute_definition_id')
                                ->label(__('admin.ui.attribute'))
                                ->options(fn (): array => ProductAttributeDefinition::query()
                                    ->ordered()
                                    ->pluck('label', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('value')
                                ->required()
                                ->maxLength(120),
                            TextInput::make('label')
                                ->required()
                                ->maxLength(160),
                            ColorPicker::make('color_hex'),
                            KeyValue::make('label_translations')
                                ->keyLabel(__('admin.ui.locale'))
                                ->valueLabel(__('admin.ui.label'))
                                ->columnSpanFull(),
                            Toggle::make('is_active')
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
            ->modifyQueryUsing(fn ($query) => $query->with('definition'))
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('definition.label')
                    ->label(__('admin.ui.attribute'))
                    ->searchable(),
                TextColumn::make('value')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('label')
                    ->searchable(),
                ColorColumn::make('color_hex')
                    ->label(__('admin.ui.color')),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('attribute_definition_id')
                    ->label(__('admin.ui.attribute'))
                    ->options(fn (): array => ProductAttributeDefinition::query()
                        ->ordered()
                        ->pluck('label', 'id')
                        ->all()),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListProductAttributeValues::route('/'),
            'create' => CreateProductAttributeValue::route('/create'),
            'edit' => EditProductAttributeValue::route('/{record}/edit'),
        ];
    }
}
