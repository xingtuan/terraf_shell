<?php

namespace App\Filament\Resources\MaterialStorySections;

use App\Filament\Resources\MaterialStorySections\Pages\CreateMaterialStorySection;
use App\Filament\Resources\MaterialStorySections\Pages\EditMaterialStorySection;
use App\Filament\Resources\MaterialStorySections\Pages\ListMaterialStorySections;
use App\Filament\Resources\MaterialStorySections\Pages\ViewMaterialStorySection;
use App\Filament\Resources\MaterialStorySections\Schemas\MaterialStorySectionForm;
use App\Filament\Resources\MaterialStorySections\Schemas\MaterialStorySectionInfolist;
use App\Filament\Resources\MaterialStorySections\Tables\MaterialStorySectionsTable;
use App\Filament\Support\PanelAccess;
use App\Models\MaterialStorySection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MaterialStorySectionResource extends Resource
{
    protected static ?string $model = MaterialStorySection::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Story Sections';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return MaterialStorySectionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MaterialStorySectionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaterialStorySectionsTable::configure($table);
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
            'index' => ListMaterialStorySections::route('/'),
            'create' => CreateMaterialStorySection::route('/create'),
            'view' => ViewMaterialStorySection::route('/{record}'),
            'edit' => EditMaterialStorySection::route('/{record}/edit'),
        ];
    }
}
