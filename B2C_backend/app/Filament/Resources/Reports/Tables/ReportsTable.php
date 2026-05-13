<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Enums\ReportResolutionAction;
use App\Enums\ReportStatus;
use App\Filament\Resources\Reports\ReportResource;
use App\Filament\Support\PanelAccess;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Services\AdminModerationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('reporter.name')
                    ->label(__('admin.ui.reporter'))
                    ->description(fn (Report $record): string => '@'.$record->reporter->username)
                    ->searchable(['name', 'username']),
                TextColumn::make('target_type')
                    ->label(__('admin.ui.target_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ReportResource::targetTypeLabel($state))
                    ->color('gray'),
                TextColumn::make('target_id')
                    ->label(__('admin.ui.target_id'))
                    ->sortable(),
                TextColumn::make('target_summary')
                    ->label(__('admin.ui.target'))
                    ->state(fn (Report $record): string => ReportResource::targetSummary($record))
                    ->limit(50),
                TextColumn::make('reason')
                    ->label(__('admin.fields.reason'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ReportStatus::tryFrom($state)?->label() ?? ucfirst($state))
                    ->color(fn (string $state): string => ReportStatus::tryFrom($state)?->color() ?? 'gray')
                    ->sortable(),
                TextColumn::make('reviewer.name')
                    ->label(__('admin.ui.reviewed_by'))
                    ->placeholder(__('admin.ui.unassigned'))
                    ->toggleable(),
                TextColumn::make('violations_count')
                    ->label(__('admin.ui.violations'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('admin.ui.created'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(ReportStatus::options()),
                SelectFilter::make('target_type')
                    ->label(__('admin.ui.target_type'))
                    ->options(ReportResource::targetTypeOptions()),
                SelectFilter::make('reviewed_by')
                    ->relationship('reviewer', 'name')
                    ->label(__('admin.ui.reviewed_by'))
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('admin.ui.created_from')),
                        DatePicker::make('created_until')
                            ->label(__('admin.ui.created_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $builder, string $date): Builder => $builder->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $builder, string $date): Builder => $builder->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('viewTarget')
                    ->label(__('admin.ui.view_target'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Report $record): ?string => ReportResource::targetAdminUrl($record))
                    ->visible(fn (Report $record): bool => filled(ReportResource::targetAdminUrl($record))),
                self::markReviewedAction(),
                self::dismissAction(),
                self::resolveAction(),
                self::hideTargetAction(),
                self::rejectTargetAction(),
                self::warnUserAction(),
                self::restrictUserAction(),
                self::banUserAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }

    private static function markReviewedAction(): Action
    {
        return Action::make('markReviewed')
            ->label(__('admin.ui.mark_as_reviewed'))
            ->color('info')
            ->visible(fn (Report $record): bool => self::canReview($record))
            ->schema(self::noteSchema(includeReviewNotice: true))
            ->requiresConfirmation()
            ->action(function (Report $record, array $data): void {
                app(AdminModerationService::class)->markReportReviewed(
                    $record,
                    PanelAccess::user(),
                    $data['moderator_note'] ?? null,
                    $data['public_note'] ?? null,
                );

                self::success(__('admin.ui.report_marked_reviewed'));
            });
    }

    private static function dismissAction(): Action
    {
        return Action::make('dismissReport')
            ->label(__('admin.ui.dismiss_report'))
            ->color('gray')
            ->visible(fn (Report $record): bool => self::canAct($record) && $record->status !== ReportStatus::Dismissed->value)
            ->schema(self::noteSchema())
            ->requiresConfirmation()
            ->action(function (Report $record, array $data): void {
                app(AdminModerationService::class)->dismissReport(
                    $record,
                    PanelAccess::user(),
                    $data['moderator_note'] ?? null,
                    $data['public_note'] ?? null,
                );

                self::success(__('admin.ui.report_dismissed'));
            });
    }

    private static function resolveAction(): Action
    {
        return Action::make('resolveReport')
            ->label(__('admin.ui.resolve_report'))
            ->color('success')
            ->visible(fn (Report $record): bool => self::canAct($record) && $record->status !== ReportStatus::Resolved->value)
            ->schema([
                Select::make('resolution_action')
                    ->label(__('admin.ui.resolution_action'))
                    ->options(ReportResolutionAction::options())
                    ->default(ReportResolutionAction::Other->value)
                    ->required(),
                ...self::noteSchema(),
            ])
            ->requiresConfirmation()
            ->action(function (Report $record, array $data): void {
                app(AdminModerationService::class)->resolveReport(
                    $record,
                    PanelAccess::user(),
                    $data['resolution_action'] ?? ReportResolutionAction::Other->value,
                    $data['moderator_note'] ?? null,
                    $data['public_note'] ?? null,
                );

                self::success(__('admin.ui.report_resolved'));
            });
    }

    private static function hideTargetAction(): Action
    {
        return self::targetAction(
            'hideTargetResolve',
            __('admin.ui.hide_target_and_resolve'),
            'warning',
            fn (AdminModerationService $service, Report $record, array $data): Report => $service->resolveReportAndHideTarget(
                $record,
                PanelAccess::user(),
                $data['moderator_note'] ?? null,
                $data['public_note'] ?? null,
            ),
            __('admin.ui.report_target_hidden_resolved')
        );
    }

    private static function rejectTargetAction(): Action
    {
        return self::targetAction(
            'rejectTargetResolve',
            __('admin.ui.reject_target_and_resolve'),
            'danger',
            fn (AdminModerationService $service, Report $record, array $data): Report => $service->resolveReportAndRejectTarget(
                $record,
                PanelAccess::user(),
                $data['moderator_note'] ?? null,
                $data['public_note'] ?? null,
            ),
            __('admin.ui.report_target_rejected_resolved')
        );
    }

    private static function warnUserAction(): Action
    {
        return self::userAction(
            'warnUserResolve',
            __('admin.ui.warn_user_and_resolve'),
            'warning',
            fn (AdminModerationService $service, Report $record, array $data): Report => $service->resolveReportAndWarnUser(
                $record,
                PanelAccess::user(),
                $data['moderator_note'] ?? null,
                $data['public_note'] ?? null,
            ),
            __('admin.ui.report_user_warned_resolved')
        );
    }

    private static function restrictUserAction(): Action
    {
        return self::userAction(
            'restrictUserResolve',
            __('admin.ui.restrict_user_and_resolve'),
            'danger',
            fn (AdminModerationService $service, Report $record, array $data): Report => $service->resolveReportAndRestrictUser(
                $record,
                PanelAccess::user(),
                $data['moderator_note'] ?? null,
                $data['public_note'] ?? null,
            ),
            __('admin.ui.report_user_restricted_resolved')
        );
    }

    private static function banUserAction(): Action
    {
        return self::userAction(
            'banUserResolve',
            __('admin.ui.ban_user_and_resolve'),
            'danger',
            fn (AdminModerationService $service, Report $record, array $data): Report => $service->resolveReportAndBanUser(
                $record,
                PanelAccess::user(),
                $data['moderator_note'] ?? null,
                $data['public_note'] ?? null,
            ),
            __('admin.ui.report_user_banned_resolved')
        );
    }

    private static function targetAction(string $name, string $label, string $color, callable $handler, string $successTitle): Action
    {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->visible(fn (Report $record): bool => self::canAct($record) && self::isContentTarget($record))
            ->schema(self::noteSchema())
            ->requiresConfirmation()
            ->action(function (Report $record, array $data) use ($handler, $successTitle): void {
                $handler(app(AdminModerationService::class), $record, $data);
                self::success($successTitle);
            });
    }

    private static function userAction(string $name, string $label, string $color, callable $handler, string $successTitle): Action
    {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->visible(fn (Report $record): bool => self::canAct($record) && self::targetUser($record) !== null)
            ->schema(self::noteSchema())
            ->requiresConfirmation()
            ->action(function (Report $record, array $data) use ($handler, $successTitle): void {
                $handler(app(AdminModerationService::class), $record, $data);
                self::success($successTitle);
            });
    }

    /**
     * @return array<int, Placeholder|Textarea>
     */
    private static function noteSchema(bool $includeReviewNotice = false): array
    {
        return [
            ...($includeReviewNotice ? [
                Placeholder::make('review_notice')
                    ->label(__('admin.ui.review_notice'))
                    ->content(__('admin.ui.mark_reviewed_does_not_resolve')),
            ] : []),
            Textarea::make('moderator_note')
                ->label(__('admin.ui.internal_moderator_note'))
                ->rows(4),
            Textarea::make('public_note')
                ->label(__('admin.ui.public_note'))
                ->rows(4),
        ];
    }

    private static function canAct(Report $record): bool
    {
        return PanelAccess::isStaff();
    }

    private static function canReview(Report $record): bool
    {
        return self::canAct($record)
            && $record->status !== ReportStatus::Reviewed->value
            && $record->target !== null;
    }

    private static function isContentTarget(Report $record): bool
    {
        return $record->target instanceof Post || $record->target instanceof Comment;
    }

    private static function targetUser(Report $record): ?User
    {
        return match (true) {
            $record->target instanceof Post => $record->target->loadMissing('user')->user,
            $record->target instanceof Comment => $record->target->loadMissing('user')->user,
            $record->target instanceof User => $record->target,
            default => null,
        };
    }

    private static function success(string $title): void
    {
        Notification::make()
            ->title($title)
            ->success()
            ->send();
    }
}
