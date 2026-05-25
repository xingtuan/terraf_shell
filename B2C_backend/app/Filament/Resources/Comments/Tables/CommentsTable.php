<?php

namespace App\Filament\Resources\Comments\Tables;

use App\Enums\ContentStatus;
use App\Filament\Support\PanelAccess;
use App\Models\Comment;
use App\Services\AdminModerationService;
use App\Enums\UserViolationStatus;
use App\Enums\UserViolationType;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('post.title')
                    ->label(__('admin.ui.concept'))
                    ->searchable()
                    ->limit(50),
                TextColumn::make('user.name')
                    ->label(__('admin.ui.author'))
                    ->description(fn (Comment $record): string => collect([
                        '@'.$record->user->username,
                        $record->user->profile?->school_or_company,
                    ])->filter()->implode(' · '))
                    ->searchable(['name', 'username']),
                TextColumn::make('parent_id')
                    ->label(__('admin.ui.thread'))
                    ->formatStateUsing(fn (?int $state): string => $state ? '#'.$state : __('admin.ui.top_level')),
                TextColumn::make('content')
                    ->searchable()
                    ->limit(70),
                TextColumn::make('likes_count')
                    ->label(__('admin.ui.likes'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('replies_count')
                    ->label(__('admin.ui.replies'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('reports_count')
                    ->label(__('admin.ui.reports'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ContentStatus::tryFrom($state)?->label() ?? ucfirst($state))
                    ->color(fn (string $state): string => ContentStatus::tryFrom($state)?->color() ?? 'gray')
                    ->sortable(),
                TextColumn::make('sensitive_word_flag')
                    ->label(__('admin.ui.review_reason'))
                    ->badge()
                    ->state(function (Comment $record): ?string {
                        if ($record->relationLoaded('openSensitiveWordViolation') && $record->openSensitiveWordViolation !== null) {
                            return __('admin.ui.sensitive_word_detected');
                        }
                        return null;
                    })
                    ->color('warning')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('admin.ui.created'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ContentStatus::options()),
                SelectFilter::make('post_id')
                    ->relationship('post', 'title')
                    ->label(__('admin.ui.concept'))
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('reported')
                    ->label(__('admin.ui.reported'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('reports'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('reports'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                Filter::make('author')
                    ->label(__('admin.ui.author_summary'))
                    ->schema([
                        TextInput::make('creator')
                            ->label(__('admin.ui.creator')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['creator'] ?? null),
                            fn (Builder $builder): Builder => $builder->whereHas('user', function (Builder $userQuery) use ($data): void {
                                $search = trim((string) $data['creator']);
                                $userQuery
                                    ->where('name', 'like', '%'.$search.'%')
                                    ->orWhere('username', 'like', '%'.$search.'%');
                            })
                        );
                    }),
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
                self::statusAction('approve', __('admin.actions.approve'), ContentStatus::Approved->value, 'success'),
                self::statusAction('reject', __('admin.actions.reject'), ContentStatus::Rejected->value, 'danger'),
                self::statusAction('hide', __('admin.actions.hide'), ContentStatus::Hidden->value, 'gray'),
                DeleteAction::make()
                    ->visible(fn (): bool => PanelAccess::isStaff()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approveSelected')
                        ->label(__('admin.ui.approve_selected'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                app(AdminModerationService::class)->updateCommentStatus(
                                    $record,
                                    ContentStatus::Approved->value,
                                    PanelAccess::user(),
                                );
                            }

                            Notification::make()
                                ->title(__('admin.ui.selected_comments_approved_successfully'))
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => PanelAccess::isStaff()),
                ]),
            ]);
    }

    private static function statusAction(string $name, string $label, string $status, string $color): Action
    {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->visible(fn (Comment $record): bool => PanelAccess::isStaff() && $record->status !== $status)
            ->schema([
                Textarea::make('reason')
                    ->label(__('admin.ui.moderation_note'))
                    ->rows(3),
            ])
            ->requiresConfirmation()
            ->action(function (Comment $record, array $data) use ($status, $label): void {
                app(AdminModerationService::class)->updateCommentStatus(
                    $record,
                    $status,
                    PanelAccess::user(),
                    $data['reason'] ?? null,
                );

                Notification::make()
                    ->title(__('admin.ui.comment_status_updated_to_label', ['label' => $label]))
                    ->success()
                    ->send();
            });
    }
}
