<?php

namespace App\Filament\Resources\AdminActionLogs;

use App\Filament\Resources\AdminActionLogs\Pages\CreateAdminActionLog;
use App\Filament\Resources\AdminActionLogs\Pages\EditAdminActionLog;
use App\Filament\Resources\AdminActionLogs\Pages\ListAdminActionLogs;
use App\Filament\Resources\AdminActionLogs\Pages\ViewAdminActionLog;
use App\Filament\Resources\AdminActionLogs\Schemas\AdminActionLogForm;
use App\Filament\Resources\AdminActionLogs\Schemas\AdminActionLogInfolist;
use App\Filament\Resources\AdminActionLogs\Tables\AdminActionLogsTable;
use App\Filament\Support\PanelAccess;
use App\Models\AdminActionLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminActionLogResource extends Resource
{
    protected static ?string $model = AdminActionLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Governance';

    protected static ?string $navigationLabel = 'Admin Action Logs';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return AdminActionLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AdminActionLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminActionLogsTable::configure($table);
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
            ->with(['actor.profile', 'targetUser.profile', 'subject'])
            ->latest();
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
        return false;
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
            'index' => ListAdminActionLogs::route('/'),
            'create' => CreateAdminActionLog::route('/create'),
            'view' => ViewAdminActionLog::route('/{record}'),
            'edit' => EditAdminActionLog::route('/{record}/edit'),
        ];
    }
}
