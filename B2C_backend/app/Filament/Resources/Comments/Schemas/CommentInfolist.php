<?php

namespace App\Filament\Resources\Comments\Schemas;

use App\Enums\ContentStatus;
use App\Filament\Resources\ModerationLogs\ModerationLogResource;
use App\Models\Comment;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommentInfolist
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
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ContentStatus::tryFrom($state)?->label() ?? ucfirst($state))
                                    ->color(fn (string $state): string => ContentStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('post.title')
                                    ->label(__('admin.ui.concept'))
                                    ->columnSpanFull(),
                                TextEntry::make('user.name')
                                    ->label(__('admin.ui.author')),
                                TextEntry::make('user.username')
                                    ->label(__('admin.ui.username'))
                                    ->state(fn ($record): string => '@'.$record->user->username),
                                TextEntry::make('user.profile.school_or_company')
                                    ->label(__('admin.ui.school_company'))
                                    ->placeholder(__('admin.ui.no_organization_provided')),
                                TextEntry::make('parent.content')
                                    ->label(__('admin.ui.parent_comment'))
                                    ->placeholder(__('admin.ui.top_level_comment'))
                                    ->limit(120)
                                    ->columnSpanFull(),
                                TextEntry::make('likes_count')
                                    ->label(__('admin.ui.likes')),
                                TextEntry::make('reports_count')
                                    ->label(__('admin.ui.reports')),
                                TextEntry::make('replies_count')
                                    ->label(__('admin.ui.replies')),
                                TextEntry::make('created_at')
                                    ->label(__('admin.ui.created'))
                                    ->dateTime(),
                            ]),
                    ]),
                Section::make(__('admin.ui.comment'))
                    ->schema([
                        TextEntry::make('content')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.replies'))
                    ->schema([
                        RepeatableEntry::make('replies')
                            ->label(__('admin.ui.replies'))
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label(__('admin.ui.author')),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ContentStatus::tryFrom($state)?->label() ?? ucfirst($state))
                                    ->color(fn (string $state): string => ContentStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('content')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make(__('admin.ui.sensitive_word_detection'))
                    ->schema([
                        TextEntry::make('sensitive_word_hit')
                            ->label(__('admin.ui.sensitive_word_detected'))
                            ->state(function (Comment $record): string {
                                $violation = $record->relationLoaded('openSensitiveWordViolation')
                                    ? $record->openSensitiveWordViolation
                                    : $record->openSensitiveWordViolation()->first();
                                return $violation !== null ? __('admin.system.yes') : __('admin.system.no');
                            })
                            ->badge()
                            ->color(function (Comment $record): string {
                                $violation = $record->relationLoaded('openSensitiveWordViolation')
                                    ? $record->openSensitiveWordViolation
                                    : $record->openSensitiveWordViolation()->first();
                                return $violation !== null ? 'danger' : 'success';
                            }),
                        TextEntry::make('sensitive_matched_fields')
                            ->label(__('admin.ui.matched_fields'))
                            ->state(function (Comment $record): string {
                                $violation = $record->relationLoaded('openSensitiveWordViolation')
                                    ? $record->openSensitiveWordViolation
                                    : $record->openSensitiveWordViolation()->first();
                                $fields = $violation?->metadata['matched_fields'] ?? [];
                                return $fields !== [] ? implode(', ', (array) $fields) : '—';
                            })
                            ->placeholder('—'),
                        TextEntry::make('sensitive_matched_count')
                            ->label(__('admin.ui.matched_count'))
                            ->state(function (Comment $record): int|string {
                                $violation = $record->relationLoaded('openSensitiveWordViolation')
                                    ? $record->openSensitiveWordViolation
                                    : $record->openSensitiveWordViolation()->first();
                                $terms = $violation?->metadata['matched_terms'] ?? [];
                                return count((array) $terms) ?: '—';
                            }),
                        TextEntry::make('sensitive_matched_terms')
                            ->label(__('admin.ui.matched_terms'))
                            ->state(function (Comment $record): string {
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
                Section::make(__('admin.ui.moderation_history'))
                    ->schema([
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
                                    ->placeholder(__('admin.ui.no_note_provided'))
                                    ->columnSpanFull(),
                                TextEntry::make('created_at')
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
