<?php

namespace App\Filament\Resources\MaterialProperties;

use App\Filament\Resources\MaterialProperties\Pages\CreateMaterialProperty;
use App\Filament\Resources\MaterialProperties\Pages\EditMaterialProperty;
use App\Filament\Resources\MaterialProperties\Pages\ListMaterialProperties;
use App\Filament\Support\PanelAccess;
use App\Models\MaterialProperty;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MaterialPropertyResource extends Resource
{
    protected static ?string $model = MaterialProperty::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Material Properties';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('key')
                    ->required()
                    ->maxLength(255),
                Select::make('locale')
                    ->options(self::localeOptions())
                    ->required(),
                TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                TextInput::make('value')
                    ->required()
                    ->maxLength(255),
                TextInput::make('comparison')
                    ->required()
                    ->maxLength(255),
                TextInput::make('icon')
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->numeric()
                    ->required()
                    ->default(0),
                Toggle::make('is_active')
                    ->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('locale')
                    ->badge()
                    ->sortable(),
                TextColumn::make('label')
                    ->searchable(),
                TextColumn::make('value')
                    ->limit(40),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('locale')
                    ->options(self::localeOptions()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => ListMaterialProperties::route('/'),
            'create' => CreateMaterialProperty::route('/create'),
            'edit' => EditMaterialProperty::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function localeOptions(): array
    {
        return [
            'en' => 'en',
            'ko' => 'ko',
            'zh' => 'zh',
        ];
    }
}
