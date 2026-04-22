<?php

namespace App\Filament\Resources\MaterialApplications;

use App\Filament\Resources\MaterialApplications\Pages\CreateMaterialApplication;
use App\Filament\Resources\MaterialApplications\Pages\EditMaterialApplication;
use App\Filament\Resources\MaterialApplications\Pages\ListMaterialApplications;
use App\Filament\Resources\MaterialApplications\Pages\ViewMaterialApplication;
use App\Filament\Resources\MaterialApplications\Schemas\MaterialApplicationForm;
use App\Filament\Resources\MaterialApplications\Schemas\MaterialApplicationInfolist;
use App\Filament\Resources\MaterialApplications\Tables\MaterialApplicationsTable;
use App\Filament\Support\PanelAccess;
use App\Models\MaterialApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MaterialApplicationResource extends Resource
{
    protected static ?string $model = MaterialApplication::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Applications';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return MaterialApplicationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MaterialApplicationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaterialApplicationsTable::configure($table);
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
            'index' => ListMaterialApplications::route('/'),
            'create' => CreateMaterialApplication::route('/create'),
            'view' => ViewMaterialApplication::route('/{record}'),
            'edit' => EditMaterialApplication::route('/{record}/edit'),
        ];
    }
}
