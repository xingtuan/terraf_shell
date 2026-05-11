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
                    ->label(__('admin.ui.image'))
                    ->state(fn (Post $record): ?string => $record->coverImageUrl())
                    ->defaultImageUrl('https://placehold.co/96x64?text=Post')
                    ->square(),
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('admin.ui.title'))
                    ->searchable()
                    ->sortable()
                    ->limit(60),
                TextColumn::make('user.name')
                    ->label(__('admin.ui.creator'))
                    ->description(fn (Post $record): string => collect([
                        '@'.$record->user->username,
                        $record->user->profile?->school_or_company,
                        $record->user->profile?->region,
                    ])->filter()->implode(' · '))
                    ->searchable(['name', 'username'])
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label(__('admin.ui.category'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ContentStatus::tryFrom($state)?->label() ?? ucfirst($state))
                    ->color(fn (string $state): string => ContentStatus::tryFrom($state)?->color() ?? 'gray')
                    ->sortable(),
                IconColumn::make('is_pinned')
                    ->label(__('admin.ui.pinned'))
                    ->boolean(),
                IconColumn::make('is_featured')
                    ->label(__('admin.ui.featured'))
                    ->boolean(),
                IconColumn::make('is_demo_content')
                    ->label(__('admin.ui.demo'))
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('fundingCampaign.support_enabled')
                    ->label(__('admin.ui.support'))
                    ->boolean()
                    ->state(fn (Post $record): bool => (bool) ($record->fundingCampaign?->support_enabled ?? false)),
                TextColumn::make('likes_count')
                    ->label(__('admin.ui.likes'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('comments_count')
                    ->label(__('admin.ui.comments'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('favorites_count')
                    ->label(__('admin.ui.favorites'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('engagement_score')
                    ->label(__('admin.ui.engagement'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('trending_score')
                    ->label(__('admin.ui.trending'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('views_count')
                    ->label(__('admin.ui.views'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('reports_count')
                    ->label(__('admin.ui.reports'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('admin.ui.created'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label(__('admin.ui.published'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ContentStatus::options()),
                SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label(__('admin.ui.category'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('tag')
                    ->label(__('admin.ui.tag'))
                    ->options(fn (): array => Tag::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $builder): Builder => $builder->whereHas(
                            'tags',
                            fn (Builder $tagQuery): Builder => $tagQuery->whereKey((int) $data['value'])
                        )
                    )),
                TernaryFilter::make('is_featured')
                    ->label(__('admin.ui.featured')),
                TernaryFilter::make('is_pinned')
                    ->label(__('admin.ui.pinned')),
                TernaryFilter::make('support_enabled')
                    ->label(__('admin.ui.support_enabled'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('fundingCampaign', fn (Builder $campaignQuery): Builder => $campaignQuery->where('support_enabled', true)),
                        false: fn (Builder $query): Builder => $query->where(function (Builder $builder): void {
                            $builder
                                ->whereDoesntHave('fundingCampaign')
                                ->orWhereHas('fundingCampaign', fn (Builder $campaignQuery): Builder => $campaignQuery->where('support_enabled', false));
                        }),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('is_demo_content')
                    ->label(__('admin.ui.demo_content')),
                Filter::make('creator_profile')
                    ->label(__('admin.ui.creator_profile'))
                    ->schema([
                        TextInput::make('creator')
                            ->label(__('admin.ui.creator')),
                        TextInput::make('school_or_company')
                            ->label(__('admin.ui.school_company_2')),
                        TextInput::make('region')
                            ->label(__('admin.ui.region')),
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
                            ->label(__('admin.ui.created_from')),
                        DatePicker::make('created_until')
                            ->label(__('admin.ui.created_until')),
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
                self::statusAction('approve', __('admin.actions.approve'), ContentStatus::Approved->value, 'success'),
                self::statusAction('reject', __('admin.actions.reject'), ContentStatus::Rejected->value, 'danger'),
                self::statusAction('hide', __('admin.actions.hide'), ContentStatus::Hidden->value, 'gray'),
                Action::make('pin')
                    ->label(__('admin.ui.pin'))
                    ->icon('heroicon-o-map-pin')
                    ->color('warning')
                    ->visible(fn (Post $record): bool => PanelAccess::isAdmin() && ! $record->is_pinned)
                    ->action(function (Post $record): void {
                        $record->loadMissing('user');
                        $record->forceFill(['is_pinned' => true])->save();
                        app(GovernanceService::class)->recordAdminAction(
                            PanelAccess::user(),
                            'post.pin_enabled',
                            __('admin.ui.concept_pinned_in_admin'),
                            ['is_pinned' => true],
                            $record,
                            $record->user,
                        );

                        Notification::make()
                            ->title(__('admin.ui.concept_pinned_successfully'))
                            ->success()
                            ->send();
                    }),
                Action::make('unpin')
                    ->label(__('admin.ui.unpin'))
                    ->icon('heroicon-o-map-pin')
                    ->color('gray')
                    ->visible(fn (Post $record): bool => PanelAccess::isAdmin() && $record->is_pinned)
                    ->action(function (Post $record): void {
                        $record->loadMissing('user');
                        $record->forceFill(['is_pinned' => false])->save();
                        app(GovernanceService::class)->recordAdminAction(
                            PanelAccess::user(),
                            'post.pin_disabled',
                            __('admin.ui.concept_unpinned_in_admin'),
                            ['is_pinned' => false],
                            $record,
                            $record->user,
                        );

                        Notification::make()
                            ->title(__('admin.ui.concept_unpinned_successfully'))
                            ->success()
                            ->send();
                    }),
                Action::make('feature')
                    ->label(__('admin.ui.feature'))
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
                            ->title(__('admin.ui.concept_featured_successfully'))
                            ->success()
                            ->send();
                    }),
                Action::make('unfeature')
                    ->label(__('admin.ui.unfeature'))
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
                            ->title(__('admin.ui.concept_unfeatured_successfully'))
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (): bool => PanelAccess::isAdmin()),
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
                                app(AdminModerationService::class)->updatePostStatus(
                                    $record,
                                    ContentStatus::Approved->value,
                                    PanelAccess::user(),
                                );
                            }

                            Notification::make()
                                ->title(__('admin.ui.selected_concepts_approved_successfully'))
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('deleteDemoContent')
                        ->label(__('admin.actions.clean_demo_content'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (): bool => PanelAccess::isAdmin())
                        ->action(function (): void {
                            Post::query()
                                ->where('is_demo_content', true)
                                ->delete();

                            Notification::make()
                                ->title(__('admin.notifications.demo_content_cleaned'))
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
                    ->label(__('admin.ui.moderation_note'))
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
                    ->title(__('admin.ui.concept_status_updated_to_label', ['label' => $label]))
                    ->success()
                    ->send();
            });
    }
}
