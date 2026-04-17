<?php

namespace App\Filament\Resources\Comments\Tables;

use App\Enums\ContentStatus;
use App\Filament\Support\PanelAccess;
use App\Models\Comment;
use App\Services\AdminModerationService;
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
                    ->label('Concept')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('user.name')
                    ->label('Author')
                    ->description(fn (Comment $record): string => collect([
                        '@'.$record->user->username,
                        $record->user->profile?->school_or_company,
                    ])->filter()->implode(' · '))
                    ->searchable(['name', 'username']),
                TextColumn::make('parent_id')
                    ->label('Thread')
                    ->formatStateUsing(fn (?int $state): string => $state ? '#'.$state : 'Top-level'),
                TextColumn::make('content')
                    ->searchable()
                    ->limit(70),
                TextColumn::make('likes_count')
                    ->label('Likes')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('replies_count')
                    ->label('Replies')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('reports_count')
                    ->label('Reports')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ContentStatus::tryFrom($state)?->label() ?? ucfirst($state))
                    ->color(fn (string $state): string => ContentStatus::tryFrom($state)?->color() ?? 'gray')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ContentStatus::options()),
                SelectFilter::make('post_id')
                    ->relationship('post', 'title')
                    ->label('Concept')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('reported')
                    ->label('Reported')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('reports'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('reports'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                Filter::make('author')
                    ->label('Author summary')
                    ->schema([
                        TextInput::make('creator')
                            ->label('Creator'),
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
                            ->label('Created from'),
                        DatePicker::make('created_until')
                            ->label('Created until'),
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
                self::statusAction('approve', 'Approve', ContentStatus::Approved->value, 'success'),
                self::statusAction('reject', 'Reject', ContentStatus::Rejected->value, 'danger'),
                self::statusAction('hide', 'Hide', ContentStatus::Hidden->value, 'gray'),
                DeleteAction::make()
                    ->visible(fn (): bool => PanelAccess::isStaff()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approveSelected')
                        ->label('Approve selected')
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
                                ->title('Selected comments approved successfully.')
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
                    ->label('Moderation note')
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
                    ->title("Comment status updated to {$label}.")
                    ->success()
                    ->send();
            });
    }
}
