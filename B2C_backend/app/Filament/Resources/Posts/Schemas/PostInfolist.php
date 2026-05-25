<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\ContentStatus;
use App\Filament\Resources\ModerationLogs\ModerationLogResource;
use App\Models\Post;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Models\UserViolation;

class PostInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.overview'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('id'),
                                TextEntry::make('slug'),
                                TextEntry::make('title')
                                    ->columnSpanFull(),
                                TextEntry::make('user.name')
                                    ->label(__('admin.ui.creator')),
                                TextEntry::make('user.username')
                                    ->label(__('admin.ui.username'))
                                    ->state(fn (Post $record): string => '@'.$record->user->username),
                                TextEntry::make('user.role')
                                    ->label(__('admin.ui.creator_role'))
                                    ->badge(),
                                TextEntry::make('user.profile.school_or_company')
                                    ->label(__('admin.ui.school_company'))
                                    ->placeholder(__('admin.ui.no_organization_assigned')),
                                TextEntry::make('user.profile.region')
                                    ->label(__('admin.ui.region'))
                                    ->placeholder(__('admin.ui.no_region_assigned')),
                                TextEntry::make('category.name')
                                    ->label(__('admin.ui.category'))
                                    ->placeholder(__('admin.ui.no_category_assigned')),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ContentStatus::tryFrom($state)?->label() ?? ucfirst($state))
                                    ->color(fn (string $state): string => ContentStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('published_at')
                                    ->dateTime()
                                    ->placeholder(__('admin.ui.not_published')),
                                TextEntry::make('is_pinned')
                                    ->label(__('admin.ui.pinned'))
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? __('admin.system.yes') : __('admin.system.no'))
                                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),
                                TextEntry::make('is_featured')
                                    ->label(__('admin.ui.featured'))
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? __('admin.system.yes') : __('admin.system.no'))
                                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                                TextEntry::make('featuredBy.name')
                                    ->label(__('admin.ui.featured_by'))
                                    ->placeholder(__('admin.ui.not_featured')),
                                TextEntry::make('featured_at')
                                    ->label(__('admin.ui.featured_at'))
                                    ->dateTime()
                                    ->placeholder(__('admin.ui.not_featured')),
                                TextEntry::make('tags_list')
                                    ->label(__('admin.ui.tags'))
                                    ->state(fn (Post $record): string => $record->tags->pluck('name')->implode(', ') ?: __('admin.ui.no_tags_assigned'))
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make(__('admin.ui.content'))
                    ->schema([
                        ImageEntry::make('cover_image_preview')
                            ->label(__('admin.ui.cover_image'))
                            ->state(fn (Post $record): ?string => $record->coverImageUrl())
                            ->checkFileExistence(false)
                            ->height(220)
                            ->columnSpanFull(),
                        TextEntry::make('excerpt')
                            ->placeholder(__('admin.ui.no_excerpt_available'))
                            ->columnSpanFull(),
                        TextEntry::make('funding_url')
                            ->label(__('admin.ui.funding_url'))
                            ->placeholder(__('admin.ui.no_funding_link_attached'))
                            ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                            ->openUrlInNewTab()
                            ->columnSpanFull(),
                        TextEntry::make('content')
                            ->columnSpanFull(),
                        ViewEntry::make('content_images_preview')
                            ->label(__('admin.ui.content_images'))
                            ->view('filament.components.media-image-grid', fn (Post $record): array => [
                                'urls' => $record->contentImageUrls(),
                            ])
                            ->visible(fn (Post $record): bool => count($record->contentImageUrls()) > 0)
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.discovery_metrics'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('likes_count')
                                    ->label(__('admin.ui.likes')),
                                TextEntry::make('comments_count')
                                    ->label(__('admin.ui.comments')),
                                TextEntry::make('favorites_count')
                                    ->label(__('admin.ui.favorites')),
                                TextEntry::make('engagement_score')
                                    ->label(__('admin.ui.engagement')),
                                TextEntry::make('trending_score')
                                    ->label(__('admin.ui.trending')),
                                TextEntry::make('views_count')
                                    ->label(__('admin.ui.views')),
                            ]),
                    ]),
                Section::make(__('admin.ui.funding'))
                    ->schema([
                        TextEntry::make('fundingCampaign.support_enabled')
                            ->label(__('admin.ui.support_enabled'))
                            ->state(fn (Post $record): string => ($record->fundingCampaign?->support_enabled ?? false) ? __('admin.system.yes') : __('admin.system.no'))
                            ->badge()
                            ->color(fn (string $state): string => $state === __('admin.system.yes') ? 'success' : 'gray'),
                        TextEntry::make('fundingCampaign.campaign_status')
                            ->label(__('admin.ui.campaign_status'))
                            ->badge()
                            ->placeholder(__('admin.ui.no_campaign_attached')),
                        TextEntry::make('fundingCampaign.support_button_text')
                            ->label(__('admin.ui.support_button'))
                            ->placeholder(__('admin.ui.no_campaign_attached')),
                        TextEntry::make('fundingCampaign.external_crowdfunding_url')
                            ->label(__('admin.ui.crowdfunding_url'))
                            ->placeholder(__('admin.ui.no_campaign_attached'))
                            ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                            ->openUrlInNewTab(),
                        TextEntry::make('fundingCampaign.target_amount')
                            ->money('USD')
                            ->placeholder(__('admin.ui.no_target_set')),
                        TextEntry::make('fundingCampaign.pledged_amount')
                            ->money('USD')
                            ->placeholder(__('admin.ui.no_pledged_amount_set')),
                        TextEntry::make('fundingCampaign.backer_count')
                            ->label(__('admin.ui.backers'))
                            ->placeholder(__('admin.ui.no_backer_data')),
                        TextEntry::make('fundingCampaign.reward_description')
                            ->label(__('admin.ui.reward_description'))
                            ->placeholder(__('admin.ui.no_reward_description'))
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.media'))
                    ->schema([
                        RepeatableEntry::make('media')
                            ->label(__('admin.ui.idea_media'))
                            ->schema([
                                ImageEntry::make('thumbnail_url')
                                    ->label(__('admin.ui.preview'))
                                    ->state(fn ($record): ?string => $record?->thumbnail_url)
                                    ->checkFileExistence(false)
                                    ->height(140),
                                TextEntry::make('title')
                                    ->placeholder(__('admin.ui.untitled_asset')),
                                TextEntry::make('kind')
                                    ->badge(),
                                TextEntry::make('media_type')
                                    ->label(__('admin.ui.type'))
                                    ->badge(),
                                TextEntry::make('original_name')
                                    ->label(__('admin.ui.filename'))
                                    ->placeholder(__('admin.ui.no_uploaded_file')),
                                TextEntry::make('mime_type')
                                    ->label(__('admin.ui.mime'))
                                    ->placeholder(__('admin.ui.not_available')),
                                TextEntry::make('size_bytes')
                                    ->label(__('admin.ui.size_bytes'))
                                    ->numeric()
                                    ->placeholder(__('admin.ui.not_available')),
                                TextEntry::make('url')
                                    ->label(__('admin.ui.stored_url'))
                                    ->placeholder(__('admin.ui.no_stored_url'))
                                    ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                                    ->openUrlInNewTab(),
                                TextEntry::make('external_url')
                                    ->label(__('admin.ui.external_url'))
                                    ->placeholder(__('admin.ui.no_external_url'))
                                    ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                                    ->openUrlInNewTab(),
                                TextEntry::make('alt_text')
                                    ->label(__('admin.ui.alt_text'))
                                    ->placeholder(__('admin.ui.no_alt_text_provided')),
                            ]),
                    ]),
                Section::make(__('admin.ui.sensitive_word_detection'))
                    ->schema([
                        TextEntry::make('sensitive_word_hit')
                            ->label(__('admin.ui.sensitive_word_detected'))
                            ->state(function (Post $record): string {
                                $violation = $record->relationLoaded('openSensitiveWordViolation')
                                    ? $record->openSensitiveWordViolation
                                    : $record->openSensitiveWordViolation()->first();
                                return $violation !== null ? __('admin.system.yes') : __('admin.system.no');
                            })
                            ->badge()
                            ->color(function (Post $record): string {
                                $violation = $record->relationLoaded('openSensitiveWordViolation')
                                    ? $record->openSensitiveWordViolation
                                    : $record->openSensitiveWordViolation()->first();
                                return $violation !== null ? 'danger' : 'success';
                            }),
                        TextEntry::make('sensitive_matched_fields')
                            ->label(__('admin.ui.matched_fields'))
                            ->state(function (Post $record): string {
                                $violation = $record->relationLoaded('openSensitiveWordViolation')
                                    ? $record->openSensitiveWordViolation
                                    : $record->openSensitiveWordViolation()->first();
                                $fields = $violation?->metadata['matched_fields'] ?? [];
                                $keys = array_keys((array) $fields);
                                return $keys !== [] ? implode(', ', $keys) : '—';
                            })
                            ->placeholder('—'),
                        TextEntry::make('sensitive_matched_count')
                            ->label(__('admin.ui.matched_count'))
                            ->state(function (Post $record): int|string {
                                $violation = $record->relationLoaded('openSensitiveWordViolation')
                                    ? $record->openSensitiveWordViolation
                                    : $record->openSensitiveWordViolation()->first();
                                $terms = $violation?->metadata['matched_terms'] ?? [];
                                return count((array) $terms) ?: '—';
                            }),
                        TextEntry::make('sensitive_matched_terms')
                            ->label(__('admin.ui.matched_terms'))
                            ->state(function (Post $record): string {
                                $violation = $record->relationLoaded('openSensitiveWordViolation')
                                    ? $record->openSensitiveWordViolation
                                    : $record->openSensitiveWordViolation()->first();
                                $terms = $violation?->metadata['matched_terms'] ?? [];
                                return $terms !== [] ? implode(', ', (array) $terms) : '—';
                            })
                            ->placeholder('—')
                            ->badge()
                            ->color('danger'),
                    ]),
                Section::make(__('admin.ui.moderation'))
                    ->schema([
                        TextEntry::make('reports_count')
                            ->label(__('admin.ui.reports'))
                            ->state(fn (Post $record): int => (int) ($record->reports_count ?? $record->reports()->count())),
                        RepeatableEntry::make('moderationLogs')
                            ->label(__('admin.ui.review_history'))
                            ->schema([
                                TextEntry::make('action')
                                    ->label(__('admin.ui.action'))
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => ModerationLogResource::actionLabel($state)),
                                TextEntry::make('actor.name')
                                    ->label(__('admin.ui.actor'))
                                    ->placeholder(__('admin.ui.system')),
                                TextEntry::make('reason')
                                    ->formatStateUsing(fn (?string $state): ?string => ModerationLogResource::reasonLabel($state))
                                    ->placeholder(__('admin.ui.no_reason_provided'))
                                    ->columnSpanFull(),
                                TextEntry::make('created_at')
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
