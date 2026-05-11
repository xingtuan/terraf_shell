<?php

namespace App\Filament\Resources\MaterialSpecs;

use App\Filament\Resources\MaterialSpecs\Pages\CreateMaterialSpec;
use App\Filament\Resources\MaterialSpecs\Pages\EditMaterialSpec;
use App\Filament\Resources\MaterialSpecs\Pages\ListMaterialSpecs;
use App\Filament\Resources\MaterialSpecs\Pages\ViewMaterialSpec;
use App\Filament\Resources\MaterialSpecs\Schemas\MaterialSpecForm;
use App\Filament\Resources\MaterialSpecs\Schemas\MaterialSpecInfolist;
use App\Filament\Resources\MaterialSpecs\Tables\MaterialSpecsTable;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\HasAdminResourceTranslations;
use App\Filament\Support\PanelAccess;
use App\Models\MaterialSpec;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MaterialSpecResource extends Resource
{
    use HasAdminResourceTranslations;

    protected static ?string $model = MaterialSpec::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::Content;

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return MaterialSpecForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MaterialSpecInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaterialSpecsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('material');
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
            'index' => ListMaterialSpecs::route('/'),
            'create' => CreateMaterialSpec::route('/create'),
            'view' => ViewMaterialSpec::route('/{record}'),
            'edit' => EditMaterialSpec::route('/{record}/edit'),
        ];
    }
}
