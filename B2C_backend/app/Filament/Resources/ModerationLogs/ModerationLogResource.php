<?php

namespace App\Filament\Resources\ModerationLogs;

use App\Filament\Resources\Comments\CommentResource as CommentAdminResource;
use App\Filament\Resources\ModerationLogs\Pages\ListModerationLogs;
use App\Filament\Resources\ModerationLogs\Pages\ViewModerationLog;
use App\Filament\Resources\ModerationLogs\Schemas\ModerationLogForm;
use App\Filament\Resources\ModerationLogs\Schemas\ModerationLogInfolist;
use App\Filament\Resources\ModerationLogs\Tables\ModerationLogsTable;
use App\Filament\Resources\Posts\PostResource as PostAdminResource;
use App\Filament\Resources\Reports\ReportResource as ReportAdminResource;
use App\Filament\Resources\Users\UserResource as UserAdminResource;
use App\Filament\Support\PanelAccess;
use App\Models\Comment;
use App\Models\ModerationLog;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ModerationLogResource extends Resource
{
    protected static ?string $model = ModerationLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Insights';

    protected static ?string $navigationLabel = 'Moderation Logs';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return ModerationLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ModerationLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ModerationLogsTable::configure($table);
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
            ->with(['actor.profile', 'targetUser.profile', 'report', 'subject'])
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

    public static function subjectTypeLabel(?string $value): string
    {
        return filled($value) ? Str::headline(class_basename($value)) : 'Unknown';
    }

    public static function subjectSummary(ModerationLog $log): string
    {
        return match (true) {
            $log->subject instanceof Post => $log->subject->title,
            $log->subject instanceof Comment => Str::limit($log->subject->content, 140),
            $log->subject instanceof User => $log->subject->name.' (@'.$log->subject->username.')',
            default => 'Subject record is no longer available.',
        };
    }

    public static function subjectAdminUrl(ModerationLog $log): ?string
    {
        return match (true) {
            $log->subject instanceof Post => PostAdminResource::getUrl('view', ['record' => $log->subject]),
            $log->subject instanceof Comment => CommentAdminResource::getUrl('view', ['record' => $log->subject]),
            $log->subject instanceof User => UserAdminResource::getUrl('view', ['record' => $log->subject]),
            $log->subject instanceof Report => ReportAdminResource::getUrl('view', ['record' => $log->subject]),
            default => null,
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModerationLogs::route('/'),
            'view' => ViewModerationLog::route('/{record}'),
        ];
    }
}
