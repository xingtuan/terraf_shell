<?php

namespace App\Filament\Resources\Comments\Schemas;

use App\Enums\ContentStatus;
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
                Section::make('Overview')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('id'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ContentStatus::tryFrom($state)?->label() ?? ucfirst($state))
                                    ->color(fn (string $state): string => ContentStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('post.title')
                                    ->label('Concept')
                                    ->columnSpanFull(),
                                TextEntry::make('user.name')
                                    ->label('Author'),
                                TextEntry::make('user.username')
                                    ->label('Username')
                                    ->state(fn ($record): string => '@'.$record->user->username),
                                TextEntry::make('user.profile.school_or_company')
                                    ->label('School / Company')
                                    ->placeholder('No organization provided.'),
                                TextEntry::make('parent.content')
                                    ->label('Parent comment')
                                    ->placeholder('Top-level comment.')
                                    ->limit(120)
                                    ->columnSpanFull(),
                                TextEntry::make('likes_count')
                                    ->label('Likes'),
                                TextEntry::make('reports_count')
                                    ->label('Reports'),
                                TextEntry::make('replies_count')
                                    ->label('Replies'),
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime(),
                            ]),
                    ]),
                Section::make('Comment')
                    ->schema([
                        TextEntry::make('content')
                            ->columnSpanFull(),
                    ]),
                Section::make('Replies')
                    ->schema([
                        RepeatableEntry::make('replies')
                            ->label('Replies')
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Author'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ContentStatus::tryFrom($state)?->label() ?? ucfirst($state))
                                    ->color(fn (string $state): string => ContentStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('content')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Moderation History')
                    ->schema([
                        RepeatableEntry::make('moderationLogs')
                            ->label('Review history')
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
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
