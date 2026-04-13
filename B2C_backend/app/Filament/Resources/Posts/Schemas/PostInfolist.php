<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\ContentStatus;
use App\Models\Post;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Overview')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('id'),
                                TextEntry::make('slug'),
                                TextEntry::make('title')
                                    ->columnSpanFull(),
                                TextEntry::make('user.name')
                                    ->label('Creator'),
                                TextEntry::make('user.username')
                                    ->label('Username')
                                    ->state(fn (Post $record): string => '@'.$record->user->username),
                                TextEntry::make('user.role')
                                    ->label('Creator role')
                                    ->badge(),
                                TextEntry::make('user.profile.school_or_company')
                                    ->label('School / Company')
                                    ->placeholder('No organization assigned.'),
                                TextEntry::make('user.profile.region')
                                    ->label('Region')
                                    ->placeholder('No region assigned.'),
                                TextEntry::make('category.name')
                                    ->label('Category')
                                    ->placeholder('No category assigned.'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ContentStatus::tryFrom($state)?->label() ?? ucfirst($state))
                                    ->color(fn (string $state): string => ContentStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('published_at')
                                    ->dateTime()
                                    ->placeholder('Not published.'),
                                TextEntry::make('is_pinned')
                                    ->label('Pinned')
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),
                                TextEntry::make('is_featured')
                                    ->label('Featured')
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                                TextEntry::make('featuredBy.name')
                                    ->label('Featured by')
                                    ->placeholder('Not featured.'),
                                TextEntry::make('featured_at')
                                    ->label('Featured at')
                                    ->dateTime()
                                    ->placeholder('Not featured.'),
                                TextEntry::make('tags_list')
                                    ->label('Tags')
                                    ->state(fn (Post $record): string => $record->tags->pluck('name')->implode(', ') ?: 'No tags assigned.')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Content')
                    ->schema([
                        TextEntry::make('excerpt')
                            ->placeholder('No excerpt available.')
                            ->columnSpanFull(),
                        TextEntry::make('content')
                            ->columnSpanFull(),
                    ]),
                Section::make('Discovery Metrics')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('likes_count')
                                    ->label('Likes'),
                                TextEntry::make('comments_count')
                                    ->label('Comments'),
                                TextEntry::make('favorites_count')
                                    ->label('Favorites'),
                                TextEntry::make('engagement_score')
                                    ->label('Engagement'),
                                TextEntry::make('trending_score')
                                    ->label('Trending'),
                                TextEntry::make('views_count')
                                    ->label('Views'),
                            ]),
                    ]),
                Section::make('Funding')
                    ->schema([
                        TextEntry::make('fundingCampaign.support_enabled')
                            ->label('Support enabled')
                            ->state(fn (Post $record): string => ($record->fundingCampaign?->support_enabled ?? false) ? 'Yes' : 'No')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'Yes' ? 'success' : 'gray'),
                        TextEntry::make('fundingCampaign.campaign_status')
                            ->label('Campaign status')
                            ->badge()
                            ->placeholder('No campaign attached.'),
                        TextEntry::make('fundingCampaign.support_button_text')
                            ->label('Support button')
                            ->placeholder('No campaign attached.'),
                        TextEntry::make('fundingCampaign.external_crowdfunding_url')
                            ->label('Crowdfunding URL')
                            ->placeholder('No campaign attached.')
                            ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                            ->openUrlInNewTab(),
                        TextEntry::make('fundingCampaign.target_amount')
                            ->money('USD')
                            ->placeholder('No target set.'),
                        TextEntry::make('fundingCampaign.pledged_amount')
                            ->money('USD')
                            ->placeholder('No pledged amount set.'),
                        TextEntry::make('fundingCampaign.backer_count')
                            ->label('Backers')
                            ->placeholder('No backer data.'),
                        TextEntry::make('fundingCampaign.reward_description')
                            ->label('Reward description')
                            ->placeholder('No reward description.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Media')
                    ->schema([
                        RepeatableEntry::make('media')
                            ->label('Idea media')
                            ->schema([
                                ImageEntry::make('thumbnail_url')
                                    ->label('Preview')
                                    ->height(140),
                                TextEntry::make('title')
                                    ->placeholder('Untitled asset.'),
                                TextEntry::make('kind')
                                    ->badge(),
                                TextEntry::make('media_type')
                                    ->label('Type')
                                    ->badge(),
                                TextEntry::make('original_name')
                                    ->label('Filename')
                                    ->placeholder('No uploaded file.'),
                                TextEntry::make('mime_type')
                                    ->label('MIME')
                                    ->placeholder('Not available.'),
                                TextEntry::make('size_bytes')
                                    ->label('Size (bytes)')
                                    ->numeric()
                                    ->placeholder('Not available.'),
                                TextEntry::make('url')
                                    ->label('Stored URL')
                                    ->placeholder('No stored URL.')
                                    ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                                    ->openUrlInNewTab(),
                                TextEntry::make('external_url')
                                    ->label('External URL')
                                    ->placeholder('No external URL.')
                                    ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                                    ->openUrlInNewTab(),
                                TextEntry::make('alt_text')
                                    ->label('Alt text')
                                    ->placeholder('No alt text provided.'),
                            ]),
                    ]),
                Section::make('Moderation')
                    ->schema([
                        TextEntry::make('reports_count')
                            ->label('Reports')
                            ->state(fn (Post $record): int => (int) ($record->reports_count ?? $record->reports()->count())),
                        RepeatableEntry::make('moderationLogs')
                            ->label('Review history')
                            ->schema([
                                TextEntry::make('action')
                                    ->badge(),
                                TextEntry::make('actor.name')
                                    ->label('Actor')
                                    ->placeholder('System'),
                                TextEntry::make('reason')
                                    ->placeholder('No reason provided.')
                                    ->columnSpanFull(),
                                TextEntry::make('created_at')
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
