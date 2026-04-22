<?php

namespace App\Filament\Resources\IdeaMedia;

use App\Filament\Resources\IdeaMedia\Pages\CreateIdeaMedia;
use App\Filament\Resources\IdeaMedia\Pages\EditIdeaMedia;
use App\Filament\Resources\IdeaMedia\Pages\ListIdeaMedia;
use App\Filament\Resources\IdeaMedia\Pages\ViewIdeaMedia;
use App\Filament\Resources\IdeaMedia\Schemas\IdeaMediaForm;
use App\Filament\Resources\IdeaMedia\Schemas\IdeaMediaInfolist;
use App\Filament\Resources\IdeaMedia\Tables\IdeaMediaTable;
use App\Filament\Support\PanelAccess;
use App\Models\IdeaMedia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IdeaMediaResource extends Resource
{
    protected static ?string $model = IdeaMedia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Community';

    protected static ?string $navigationLabel = 'Concept Media';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return IdeaMediaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return IdeaMediaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IdeaMediaTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['post.user.profile', 'post.category']);
    }

    public static function canViewAny(): bool
    {
        return PanelAccess::isStaff();
    }

    public static function canView(Model $record): bool
    {
        return PanelAccess::isStaff();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return PanelAccess::isStaff();
    }

    public static function canDelete(Model $record): bool
    {
        return PanelAccess::isStaff();
    }

    public static function canDeleteAny(): bool
    {
        return PanelAccess::isStaff();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIdeaMedia::route('/'),
            'create' => CreateIdeaMedia::route('/create'),
            'view' => ViewIdeaMedia::route('/{record}'),
            'edit' => EditIdeaMedia::route('/{record}/edit'),
        ];
    }
}
