<?php

namespace App\Filament\Resources\UserViolations;

use App\Filament\Resources\UserViolations\Pages\CreateUserViolation;
use App\Filament\Resources\UserViolations\Pages\EditUserViolation;
use App\Filament\Resources\UserViolations\Pages\ListUserViolations;
use App\Filament\Resources\UserViolations\Pages\ViewUserViolation;
use App\Filament\Resources\UserViolations\Schemas\UserViolationForm;
use App\Filament\Resources\UserViolations\Schemas\UserViolationInfolist;
use App\Filament\Resources\UserViolations\Tables\UserViolationsTable;
use App\Filament\Support\PanelAccess;
use App\Models\UserViolation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserViolationResource extends Resource
{
    protected static ?string $model = UserViolation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Governance';

    protected static ?string $navigationLabel = 'User Violations';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return UserViolationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserViolationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserViolationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user.profile', 'actor.profile', 'resolver.profile', 'subject', 'report']);
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
        return PanelAccess::isStaff();
    }

    public static function canEdit(Model $record): bool
    {
        return PanelAccess::isStaff();
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
            'index' => ListUserViolations::route('/'),
            'create' => CreateUserViolation::route('/create'),
            'view' => ViewUserViolation::route('/{record}'),
            'edit' => EditUserViolation::route('/{record}/edit'),
        ];
    }
}
