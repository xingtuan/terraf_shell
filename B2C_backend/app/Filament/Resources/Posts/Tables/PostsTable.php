<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Enums\ContentStatus;
use App\Filament\Support\PanelAccess;
use App\Models\Post;
use App\Models\Tag;
use App\Services\AdminModerationService;
use App\Services\GovernanceService;
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
                    ->state(fn (Post $record): ?string => $record->coverImageUrl())
                    ->defaultImageUrl('https://placehold.co/96x64?text=Post')
                    ->square(),
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(60),
                TextColumn::make('user.name')
                    ->label('Creator')
                    ->description(fn (Post $record): string => collect([
                        '@'.$record->user->username,
                        $record->user->profile?->school_or_company,
                        $record->user->profile?->region,
                    ])->filter()->implode(' · '))
                    ->searchable(['name', 'username'])
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->toggleable(),
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
                IconColumn::make('fundingCampaign.support_enabled')
                    ->label('Support')
                    ->boolean()
                    ->state(fn (Post $record): bool => (bool) ($record->fundingCampaign?->support_enabled ?? false)),
                TextColumn::make('likes_count')
                    ->label('Likes')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('comments_count')
                    ->label('Comments')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('favorites_count')
                    ->label('Favorites')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('engagement_score')
                    ->label('Engagement')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('trending_score')
                    ->label('Trending')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('views_count')
                    ->label('Views')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('reports_count')
                    ->label('Reports')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ContentStatus::options()),
                SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('tag')
                    ->label('Tag')
                    ->options(fn (): array => Tag::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $builder): Builder => $builder->whereHas(
                            'tags',
                            fn (Builder $tagQuery): Builder => $tagQuery->whereKey((int) $data['value'])
                        )
                    )),
                TernaryFilter::make('is_featured')
                    ->label('Featured'),
                TernaryFilter::make('is_pinned')
                    ->label('Pinned'),
                TernaryFilter::make('support_enabled')
                    ->label('Support enabled')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('fundingCampaign', fn (Builder $campaignQuery): Builder => $campaignQuery->where('support_enabled', true)),
                        false: fn (Builder $query): Builder => $query->where(function (Builder $builder): void {
                            $builder
                                ->whereDoesntHave('fundingCampaign')
                                ->orWhereHas('fundingCampaign', fn (Builder $campaignQuery): Builder => $campaignQuery->where('support_enabled', false));
                        }),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                Filter::make('creator_profile')
                    ->label('Creator profile')
                    ->schema([
                        TextInput::make('creator')
                            ->label('Creator'),
                        TextInput::make('school_or_company')
                            ->label('School / company'),
                        TextInput::make('region')
                            ->label('Region'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->whereHas('user', function (Builder $userQuery) use ($data): void {
                            $userQuery
                                ->when(
                                    filled($data['creator'] ?? null),
                                    fn (Builder $builder): Builder => $builder->where(function (Builder $creatorQuery) use ($data): void {
                                        $search = trim((string) $data['creator']);
                                        $creatorQuery
                                            ->where('name', 'like', '%'.$search.'%')
                                            ->orWhere('username', 'like', '%'.$search.'%');
                                    })
                                )
                                ->when(
                                    filled($data['school_or_company'] ?? null) || filled($data['region'] ?? null),
                                    fn (Builder $builder): Builder => $builder->whereHas('profile', function (Builder $profileQuery) use ($data): void {
                                        $profileQuery
                                            ->when(
                                                filled($data['school_or_company'] ?? null),
                                                fn (Builder $profileBuilder): Builder => $profileBuilder->where('school_or_company', 'like', '%'.trim((string) $data['school_or_company']).'%')
                                            )
                                            ->when(
                                                filled($data['region'] ?? null),
                                                fn (Builder $profileBuilder): Builder => $profileBuilder->where('region', 'like', '%'.trim((string) $data['region']).'%')
                                            );
                                    })
                                );
                        });
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
                        $record->loadMissing('user');
                        $record->forceFill(['is_pinned' => true])->save();
                        app(GovernanceService::class)->recordAdminAction(
                            PanelAccess::user(),
                            'post.pin_enabled',
                            'Concept pinned in admin.',
                            ['is_pinned' => true],
                            $record,
                            $record->user,
                        );

                        Notification::make()
                            ->title('Concept pinned successfully.')
                            ->success()
                            ->send();
                    }),
                Action::make('unpin')
                    ->label('Unpin')
                    ->icon('heroicon-o-map-pin')
                    ->color('gray')
                    ->visible(fn (Post $record): bool => PanelAccess::isAdmin() && $record->is_pinned)
                    ->action(function (Post $record): void {
                        $record->loadMissing('user');
                        $record->forceFill(['is_pinned' => false])->save();
                        app(GovernanceService::class)->recordAdminAction(
                            PanelAccess::user(),
                            'post.pin_disabled',
                            'Concept unpinned in admin.',
                            ['is_pinned' => false],
                            $record,
                            $record->user,
                        );

                        Notification::make()
                            ->title('Concept unpinned successfully.')
                            ->success()
                            ->send();
                    }),
                Action::make('feature')
                    ->label('Feature')
                    ->icon('heroicon-o-star')
                    ->color('success')
                    ->visible(fn (Post $record): bool => PanelAccess::isAdmin() && ! $record->is_featured)
                    ->action(function (Post $record): void {
                        app(AdminModerationService::class)->updatePostFeaturedStatus(
                            $record,
                            true,
                            PanelAccess::user(),
                        );

                        Notification::make()
                            ->title('Concept featured successfully.')
                            ->success()
                            ->send();
                    }),
                Action::make('unfeature')
                    ->label('Unfeature')
                    ->icon('heroicon-o-star')
                    ->color('gray')
                    ->visible(fn (Post $record): bool => PanelAccess::isAdmin() && $record->is_featured)
                    ->action(function (Post $record): void {
                        app(AdminModerationService::class)->updatePostFeaturedStatus(
                            $record,
                            false,
                            PanelAccess::user(),
                        );

                        Notification::make()
                            ->title('Concept unfeatured successfully.')
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
                                ->title('Selected concepts approved successfully.')
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
                    ->title("Concept status updated to {$label}.")
                    ->success()
                    ->send();
            });
    }
}
