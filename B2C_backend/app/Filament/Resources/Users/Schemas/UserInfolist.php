<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\AccountStatus;
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
                Section::make('User')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                ImageEntry::make('profile.avatar_url')
                                    ->label('Avatar')
                                    ->circular()
                                    ->defaultImageUrl('https://placehold.co/120x120?text=User'),
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Full name'),
                                        TextEntry::make('username'),
                                        TextEntry::make('email'),
                                        TextEntry::make('email_verification')
                                            ->label('Email verification')
                                            ->state(fn (User $record): string => $record->email_verified_at ? 'Verified' : 'Pending')
                                            ->badge()
                                            ->color(fn (string $state): string => $state === 'Verified' ? 'success' : 'warning'),
                                        TextEntry::make('role')
                                            ->badge()
                                            ->formatStateUsing(fn (string $state): string => UserRole::tryFrom($state)?->label() ?? ucfirst($state))
                                            ->color(fn (string $state): string => UserRole::tryFrom($state)?->color() ?? 'gray'),
                                        TextEntry::make('account_status')
                                            ->label('Status')
                                            ->badge()
                                            ->state(fn (User $record): string => $record->accountStatusValue())
                                            ->formatStateUsing(fn (string $state): string => AccountStatus::tryFrom($state)?->label() ?? ucfirst($state))
                                            ->color(fn (string $state): string => AccountStatus::tryFrom($state)?->color() ?? 'gray'),
                                        TextEntry::make('status_reason')
                                            ->state(fn (User $record): ?string => $record->participationRestrictionReason())
                                            ->placeholder('No restriction reason recorded.'),
                                        TextEntry::make('community_auto_approve')
                                            ->label('Direct community approval')
                                            ->badge()
                                            ->formatStateUsing(fn (bool $state): string => $state ? 'Enabled' : 'Disabled')
                                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                                        TextEntry::make('created_at')
                                            ->label('Created')
                                            ->dateTime(),
                                        TextEntry::make('updated_at')
                                            ->label('Updated')
                                            ->dateTime(),
                                    ]),
                            ]),
                    ]),
                Section::make('Profile')
                    ->schema([
                        TextEntry::make('profile.bio')
                            ->placeholder('No biography provided.')
                            ->columnSpanFull(),
                        TextEntry::make('profile.school_or_company')
                            ->placeholder('No organization set.'),
                        TextEntry::make('profile.region')
                            ->placeholder('No region set.'),
                        TextEntry::make('profile.portfolio_url')
                            ->label('Portfolio')
                            ->placeholder('No portfolio set.')
                            ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                            ->openUrlInNewTab(),
                        TextEntry::make('profile.open_to_collab')
                            ->label('Open to collaboration')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    ]),
                Section::make('Statistics')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('posts_count')
                                    ->label('Ideas submitted')
                                    ->state(fn (User $record): int => (int) ($record->posts_count ?? $record->posts()->count())),
                                TextEntry::make('comments_count')
                                    ->label('Comments')
                                    ->state(fn (User $record): int => (int) ($record->comments_count ?? $record->comments()->count())),
                                TextEntry::make('reports_received')
                                    ->label('Reports received')
                                    ->state(fn (User $record): int => $record->reportsReceivedCount()),
                                TextEntry::make('received_moderation_logs_count')
                                    ->label('Moderation actions')
                                    ->state(fn (User $record): int => (int) ($record->received_moderation_logs_count ?? $record->receivedModerationLogs()->count())),
                                TextEntry::make('received_admin_action_logs_count')
                                    ->label('Admin actions')
                                    ->state(fn (User $record): int => (int) ($record->received_admin_action_logs_count ?? $record->receivedAdminActionLogs()->count())),
                                TextEntry::make('violations_count')
                                    ->label('Violations')
                                    ->state(fn (User $record): int => (int) ($record->violations_count ?? $record->violations()->count())),
                                TextEntry::make('followers_count')
                                    ->state(fn (User $record): int => (int) ($record->followers_count ?? $record->followers()->count())),
                                TextEntry::make('following_count')
                                    ->state(fn (User $record): int => (int) ($record->following_count ?? $record->following()->count())),
                            ]),
                    ]),
                Section::make('Recent Ideas')
                    ->schema([
                        RepeatableEntry::make('recent_posts')
                            ->label('Latest concepts')
                            ->state(fn (User $record) => $record->posts()->latest()->limit(5)->get())
                            ->schema([
                                TextEntry::make('title')
                                    ->weight('bold'),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('engagement_score')
                                    ->label('Engagement'),
                                TextEntry::make('created_at')
                                    ->dateTime(),
                            ]),
                    ]),
                Section::make('Recent Governance')
                    ->schema([
                        RepeatableEntry::make('recent_violations')
                            ->label('Recent violations')
                            ->state(fn (User $record) => $record->violations()->with(['actor'])->latest('occurred_at')->limit(5)->get())
                            ->schema([
                                TextEntry::make('type')
                                    ->badge(),
                                TextEntry::make('severity')
                                    ->badge(),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('actor.name')
                                    ->label('Recorded by')
                                    ->placeholder('System'),
                                TextEntry::make('reason')
                                    ->placeholder('No reason recorded.')
                                    ->columnSpanFull(),
                                TextEntry::make('occurred_at')
                                    ->label('Occurred')
                                    ->dateTime(),
                            ]),
                        RepeatableEntry::make('recent_moderation_logs')
                            ->label('Recent moderation history')
                            ->state(fn (User $record) => $record->receivedModerationLogs()->with(['actor'])->latest()->limit(5)->get())
                            ->schema([
                                TextEntry::make('action')
                                    ->badge(),
                                TextEntry::make('actor.name')
                                    ->label('Actor')
                                    ->placeholder('System'),
                                TextEntry::make('reason')
                                    ->placeholder('No note provided.')
                                    ->columnSpanFull(),
                                TextEntry::make('created_at')
                                    ->label('When')
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
