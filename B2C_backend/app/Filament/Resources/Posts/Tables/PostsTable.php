<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Enums\ContentStatus;
use App\Filament\Support\PanelAccess;
use App\Models\Post;
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
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('Image')
                    ->state(fn (Post $record): ?string => $record->images->first()?->url)
                    ->defaultImageUrl('https://placehold.co/96x64?text=Post')
                    ->square(),
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(60),
                TextColumn::make('user.name')
                    ->label('Author')
                    ->description(fn (Post $record): string => '@'.$record->user->username)
                    ->searchable(['users.name', 'users.username'])
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ContentStatus::tryFrom($state)?->label() ?? ucfirst($state))
                    ->color(fn (string $state): string => ContentStatus::tryFrom($state)?->color() ?? 'gray')
                    ->sortable(),
                IconColumn::make('is_pinned')
                    ->label('Pinned')
                    ->boolean(),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ContentStatus::options()),
                TernaryFilter::make('is_featured')
                    ->label('Featured'),
                TernaryFilter::make('is_pinned')
                    ->label('Pinned'),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Created from'),
                        DatePicker::make('created_until')
                            ->label('Created until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                self::statusAction('approve', 'Approve', ContentStatus::Approved->value, 'success'),
                self::statusAction('reject', 'Reject', ContentStatus::Rejected->value, 'danger'),
                self::statusAction('hide', 'Hide', ContentStatus::Hidden->value, 'gray'),
                Action::make('pin')
                    ->label('Pin')
                    ->icon('heroicon-o-map-pin')
                    ->color('warning')
                    ->visible(fn (Post $record): bool => PanelAccess::isAdmin() && ! $record->is_pinned)
                    ->action(function (Post $record): void {
                        $record->forceFill(['is_pinned' => true])->save();

                        Notification::make()
                            ->title('Post pinned successfully.')
                            ->success()
                            ->send();
                    }),
                Action::make('unpin')
                    ->label('Unpin')
                    ->icon('heroicon-o-map-pin')
                    ->color('gray')
                    ->visible(fn (Post $record): bool => PanelAccess::isAdmin() && $record->is_pinned)
                    ->action(function (Post $record): void {
                        $record->forceFill(['is_pinned' => false])->save();

                        Notification::make()
                            ->title('Post unpinned successfully.')
                            ->success()
                            ->send();
                    }),
                Action::make('feature')
                    ->label('Feature')
                    ->icon('heroicon-o-star')
                    ->color('success')
                    ->visible(fn (Post $record): bool => PanelAccess::isAdmin() && ! $record->is_featured)
                    ->action(function (Post $record): void {
                        $record->forceFill(['is_featured' => true])->save();

                        Notification::make()
                            ->title('Post featured successfully.')
                            ->success()
                            ->send();
                    }),
                Action::make('unfeature')
                    ->label('Unfeature')
                    ->icon('heroicon-o-star')
                    ->color('gray')
                    ->visible(fn (Post $record): bool => PanelAccess::isAdmin() && $record->is_featured)
                    ->action(function (Post $record): void {
                        $record->forceFill(['is_featured' => false])->save();

                        Notification::make()
                            ->title('Post unfeatured successfully.')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (): bool => PanelAccess::isAdmin()),
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
                                app(AdminModerationService::class)->updatePostStatus(
                                    $record,
                                    ContentStatus::Approved->value,
                                    PanelAccess::user(),
                                );
                            }

                            Notification::make()
                                ->title('Selected posts approved successfully.')
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => PanelAccess::isAdmin()),
                ]),
            ]);
    }

    private static function statusAction(string $name, string $label, string $status, string $color): Action
    {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->visible(fn (Post $record): bool => PanelAccess::isStaff() && $record->status !== $status)
            ->schema([
                Textarea::make('reason')
                    ->label('Moderation note')
                    ->rows(3),
            ])
            ->requiresConfirmation()
            ->action(function (Post $record, array $data) use ($status, $label): void {
                app(AdminModerationService::class)->updatePostStatus(
                    $record,
                    $status,
                    PanelAccess::user(),
                    $data['reason'] ?? null,
                );

                Notification::make()
                    ->title("Post status updated to {$label}.")
                    ->success()
                    ->send();
            });
    }
}
