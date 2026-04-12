<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Filament\Infolists\Components\ImageEntry;
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
                                    ->state(fn (User $record): int => (int) ($record->posts_count ?? $record->posts()->count())),
                                TextEntry::make('comments_count')
                                    ->state(fn (User $record): int => (int) ($record->comments_count ?? $record->comments()->count())),
                                TextEntry::make('followers_count')
                                    ->state(fn (User $record): int => (int) ($record->followers_count ?? $record->followers()->count())),
                                TextEntry::make('following_count')
                                    ->state(fn (User $record): int => (int) ($record->following_count ?? $record->following()->count())),
                            ]),
                    ]),
            ]);
    }
}
