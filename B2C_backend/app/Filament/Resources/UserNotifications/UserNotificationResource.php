<?php

namespace App\Filament\Resources\UserNotifications;

use App\Filament\Resources\UserNotifications\Pages\ListUserNotifications;
use App\Filament\Resources\UserNotifications\Pages\ViewUserNotification;
use App\Filament\Resources\UserNotifications\Schemas\UserNotificationForm;
use App\Filament\Resources\UserNotifications\Schemas\UserNotificationInfolist;
use App\Filament\Resources\UserNotifications\Tables\UserNotificationsTable;
use App\Filament\Support\PanelAccess;
use App\Models\UserNotification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserNotificationResource extends Resource
{
    protected static ?string $model = UserNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Community';

    protected static ?string $navigationLabel = 'Announcements';

    protected static ?int $navigationSort = 80;

    public static function form(Schema $schema): Schema
    {
        return UserNotificationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserNotificationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserNotificationsTable::configure($table);
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
            ->with(['recipient.profile', 'actor.profile', 'target'])
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
            'index' => ListUserNotifications::route('/'),
            'view' => ViewUserNotification::route('/{record}'),
        ];
    }
}
