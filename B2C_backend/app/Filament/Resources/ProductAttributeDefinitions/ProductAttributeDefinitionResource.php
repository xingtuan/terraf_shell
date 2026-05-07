<?php

namespace App\Filament\Resources\ProductAttributeDefinitions;

use App\Filament\Resources\ProductAttributeDefinitions\Pages\CreateProductAttributeDefinition;
use App\Filament\Resources\ProductAttributeDefinitions\Pages\EditProductAttributeDefinition;
use App\Filament\Resources\ProductAttributeDefinitions\Pages\ListProductAttributeDefinitions;
use App\Filament\Support\PanelAccess;
use App\Models\ProductAttributeDefinition;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductAttributeDefinitionResource extends Resource
{
    protected static ?string $model = ProductAttributeDefinition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|\UnitEnum|null $navigationGroup = 'Shop';

    protected static ?string $navigationLabel = 'Product Attributes';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Attribute definition')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('key')
                                ->required()
                                ->maxLength(120)
                                ->unique(ignoreRecord: true),
                            TextInput::make('label')
                                ->required()
                                ->maxLength(160),
                            KeyValue::make('label_translations')
                                ->keyLabel('Locale')
                                ->valueLabel('Label')
                                ->columnSpanFull(),
                            Select::make('type')
                                ->options(ProductAttributeDefinition::TYPE_OPTIONS)
                                ->default('select')
                                ->required(),
                            TextInput::make('unit')
                                ->maxLength(40),
                            Toggle::make('is_variant_option'),
                            Toggle::make('is_filterable'),
                            Toggle::make('is_searchable'),
                            Toggle::make('is_specification')
                                ->default(true),
                            Toggle::make('is_required'),
                            Toggle::make('is_active')
                                ->default(true),
                            TextInput::make('sort_order')
                                ->numeric()
                                ->default(0),
                        ]),
                ]),
            Section::make('Values')
                ->schema([
                    Repeater::make('values')
                        ->relationship()
                        ->label('Allowed values')
                        ->addActionLabel('Add value')
                        ->reorderableWithButtons()
                        ->orderColumn('sort_order')
                        ->collapsible()
                        ->schema([
                            TextInput::make('value')
                                ->required()
                                ->maxLength(120),
                            TextInput::make('label')
                                ->required()
                                ->maxLength(160),
                            KeyValue::make('label_translations')
                                ->keyLabel('Locale')
                                ->valueLabel('Label')
                                ->columnSpanFull(),
                            ColorPicker::make('color_hex'),
                            Toggle::make('is_active')
                                ->default(true),
                            TextInput::make('sort_order')
                                ->numeric()
                                ->default(0),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('key')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge(),
                IconColumn::make('is_variant_option')
                    ->label('Variant?')
                    ->boolean(),
                IconColumn::make('is_filterable')
                    ->label('Filterable?')
                    ->boolean(),
                IconColumn::make('is_specification')
                    ->label('Specification?')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active?')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(ProductAttributeDefinition::TYPE_OPTIONS),
                TernaryFilter::make('is_variant_option'),
                TernaryFilter::make('is_filterable'),
                TernaryFilter::make('is_specification'),
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
            'index' => ListProductAttributeDefinitions::route('/'),
            'create' => CreateProductAttributeDefinition::route('/create'),
            'edit' => EditProductAttributeDefinition::route('/{record}/edit'),
        ];
    }
}
