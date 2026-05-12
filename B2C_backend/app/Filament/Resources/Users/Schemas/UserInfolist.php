<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\AccountStatus;
use App\Filament\Resources\ModerationLogs\ModerationLogResource;
use App\Enums\UserRole;
use App\Models\User;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.user'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                ImageEntry::make('profile.avatar_url')
                                    ->label(__('admin.ui.avatar'))
                                    ->circular()
                                    ->defaultImageUrl('https://placehold.co/120x120?text=User'),
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label(__('admin.ui.full_name')),
                                        TextEntry::make('username'),
                                        TextEntry::make('email'),
                                        TextEntry::make('email_verification')
                                            ->label(__('admin.ui.email_verification'))
                                            ->state(fn (User $record): string => $record->email_verified_at ? __('admin.ui.verified') : __('admin.ui.pending'))
                                            ->badge()
                                            ->color(fn (string $state): string => $state === __('admin.ui.verified') ? 'success' : 'warning'),
                                        TextEntry::make('role')
                                            ->badge()
                                            ->formatStateUsing(fn (string $state): string => UserRole::tryFrom($state)?->label() ?? ucfirst($state))
                                            ->color(fn (string $state): string => UserRole::tryFrom($state)?->color() ?? 'gray'),
                                        TextEntry::make('account_status')
                                            ->label(__('admin.ui.status'))
                                            ->badge()
                                            ->state(fn (User $record): string => $record->accountStatusValue())
                                            ->formatStateUsing(fn (string $state): string => AccountStatus::tryFrom($state)?->label() ?? ucfirst($state))
                                            ->color(fn (string $state): string => AccountStatus::tryFrom($state)?->color() ?? 'gray'),
                                        TextEntry::make('status_reason')
                                            ->state(fn (User $record): ?string => $record->participationRestrictionReason())
                                            ->placeholder(__('admin.ui.no_restriction_reason_recorded')),
                                        TextEntry::make('community_auto_approve')
                                            ->label(__('admin.ui.direct_community_approval'))
                                            ->badge()
                                            ->formatStateUsing(fn (bool $state): string => $state ? __('admin.ui.enabled') : __('admin.ui.disabled'))
                                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                                        TextEntry::make('created_at')
                                            ->label(__('admin.ui.created'))
                                            ->dateTime(),
                                        TextEntry::make('updated_at')
                                            ->label(__('admin.ui.updated'))
                                            ->dateTime(),
                                    ]),
                            ]),
                    ]),
                Section::make(__('admin.ui.profile'))
                    ->schema([
                        TextEntry::make('profile.bio')
                            ->placeholder(__('admin.ui.no_biography_provided'))
                            ->columnSpanFull(),
                        TextEntry::make('profile.school_or_company')
                            ->placeholder(__('admin.ui.no_organization_set')),
                        TextEntry::make('profile.region')
                            ->placeholder(__('admin.ui.no_region_set')),
                        TextEntry::make('profile.portfolio_url')
                            ->label(__('admin.ui.portfolio'))
                            ->placeholder(__('admin.ui.no_portfolio_set'))
                            ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                            ->openUrlInNewTab(),
                        TextEntry::make('profile.open_to_collab')
                            ->label(__('admin.ui.open_to_collaboration'))
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? __('admin.system.yes') : __('admin.system.no'))
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    ]),
                Section::make(__('admin.ui.statistics'))
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('posts_count')
                                    ->label(__('admin.ui.ideas_submitted'))
                                    ->state(fn (User $record): int => (int) ($record->posts_count ?? $record->posts()->count())),
                                TextEntry::make('comments_count')
                                    ->label(__('admin.ui.comments'))
                                    ->state(fn (User $record): int => (int) ($record->comments_count ?? $record->comments()->count())),
                                TextEntry::make('reports_received')
                                    ->label(__('admin.ui.reports_received'))
                                    ->state(fn (User $record): int => $record->reportsReceivedCount()),
                                TextEntry::make('received_moderation_logs_count')
                                    ->label(__('admin.ui.moderation_actions'))
                                    ->state(fn (User $record): int => (int) ($record->received_moderation_logs_count ?? $record->receivedModerationLogs()->count())),
                                TextEntry::make('received_admin_action_logs_count')
                                    ->label(__('admin.ui.admin_actions'))
                                    ->state(fn (User $record): int => (int) ($record->received_admin_action_logs_count ?? $record->receivedAdminActionLogs()->count())),
                                TextEntry::make('violations_count')
                                    ->label(__('admin.ui.violations'))
                                    ->state(fn (User $record): int => (int) ($record->violations_count ?? $record->violations()->count())),
                                TextEntry::make('followers_count')
                                    ->state(fn (User $record): int => (int) ($record->followers_count ?? $record->followers()->count())),
                                TextEntry::make('following_count')
                                    ->state(fn (User $record): int => (int) ($record->following_count ?? $record->following()->count())),
                            ]),
                    ]),
                Section::make(__('admin.ui.recent_ideas'))
                    ->schema([
                        RepeatableEntry::make('recent_posts')
                            ->label(__('admin.ui.latest_concepts'))
                            ->state(fn (User $record) => $record->posts()->latest()->limit(5)->get())
                            ->schema([
                                TextEntry::make('title')
                                    ->weight('bold'),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('engagement_score')
                                    ->label(__('admin.ui.engagement')),
                                TextEntry::make('created_at')
                                    ->dateTime(),
                            ]),
                    ]),
                Section::make(__('admin.ui.recent_governance'))
                    ->schema([
                        RepeatableEntry::make('recent_violations')
                            ->label(__('admin.ui.recent_violations'))
                            ->state(fn (User $record) => $record->violations()->with(['actor'])->latest('occurred_at')->limit(5)->get())
                            ->schema([
                                TextEntry::make('type')
                                    ->badge(),
                                TextEntry::make('severity')
                                    ->badge(),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('actor.name')
                                    ->label(__('admin.ui.recorded_by'))
                                    ->placeholder(__('admin.ui.system')),
                                TextEntry::make('reason')
                                    ->placeholder(__('admin.ui.no_reason_recorded'))
                                    ->columnSpanFull(),
                                TextEntry::make('occurred_at')
                                    ->label(__('admin.ui.occurred'))
                                    ->dateTime(),
                            ]),
                        RepeatableEntry::make('recent_moderation_logs')
                            ->label(__('admin.ui.recent_moderation_history'))
                            ->state(fn (User $record) => $record->receivedModerationLogs()->with(['actor'])->latest()->limit(5)->get())
                            ->schema([
                                TextEntry::make('action')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => ModerationLogResource::actionLabel($state)),
                                TextEntry::make('actor.name')
                                    ->label(__('admin.ui.actor'))
                                    ->placeholder(__('admin.ui.system')),
                                TextEntry::make('reason')
                                    ->placeholder(__('admin.ui.no_note_provided'))
                                    ->columnSpanFull(),
                                TextEntry::make('created_at')
                                    ->label(__('admin.ui.when'))
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
