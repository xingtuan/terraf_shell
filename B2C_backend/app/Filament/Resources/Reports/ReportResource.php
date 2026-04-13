<?php

namespace App\Filament\Resources\Reports;

use App\Filament\Resources\Comments\CommentResource as CommentAdminResource;
use App\Filament\Resources\Posts\PostResource as PostAdminResource;
use App\Filament\Resources\Reports\Pages\EditReport;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Filament\Resources\Reports\Pages\ViewReport;
use App\Filament\Resources\Reports\Schemas\ReportForm;
use App\Filament\Resources\Reports\Schemas\ReportInfolist;
use App\Filament\Resources\Reports\Tables\ReportsTable;
use App\Filament\Resources\Users\UserResource as UserAdminResource;
use App\Filament\Support\PanelAccess;
use App\Models\Comment;
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

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Moderation';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return ReportForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReportsTable::configure($table);
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
            ->with(['reporter.profile', 'reviewer.profile', 'target'])
            ->withCount(['violations', 'moderationLogs']);
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
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function targetTypeOptions(): array
    {
        return [
            'post' => 'Post',
            'comment' => 'Comment',
            'user' => 'User',
        ];
    }

    public static function targetTypeLabel(?string $value): string
    {
        return static::targetTypeOptions()[$value] ?? Str::headline((string) $value);
    }

    public static function targetSummary(Report $report): string
    {
        return match (true) {
            $report->target instanceof Post => $report->target->title,
            $report->target instanceof Comment => Str::limit($report->target->content, 140),
            $report->target instanceof User => $report->target->name.' (@'.$report->target->username.')',
            default => 'Target content is no longer available.',
        };
    }

    public static function targetAdminUrl(Report $report): ?string
    {
        return match (true) {
            $report->target instanceof Post => PostAdminResource::getUrl('view', ['record' => $report->target]),
            $report->target instanceof Comment => CommentAdminResource::getUrl('view', ['record' => $report->target]),
            $report->target instanceof User => UserAdminResource::getUrl('view', ['record' => $report->target]),
            default => null,
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
            'view' => ViewReport::route('/{record}'),
            'edit' => EditReport::route('/{record}/edit'),
        ];
    }
}
