<?php

namespace App\Filament\Resources\ModerationLogs\Tables;

use App\Filament\Resources\ModerationLogs\ModerationLogResource;
use App\Models\Comment;
use App\Models\ModerationLog;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ModerationLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('actor.name')
                    ->label('Actor')
                    ->default('System')
                    ->searchable(),
                TextColumn::make('action')
                    ->badge()
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Subject type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ModerationLogResource::subjectTypeLabel($state))
                    ->color('gray'),
                TextColumn::make('subject_id')
                    ->label('Subject ID')
                    ->sortable(),
                TextColumn::make('subject_summary')
                    ->label('Subject')
                    ->state(fn (ModerationLog $record): string => ModerationLogResource::subjectSummary($record))
                    ->limit(60),
                TextColumn::make('reason')
                    ->limit(60)
                    ->default('No reason provided.'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('subject_type')
                    ->options([
                        Post::class => 'Post',
                        Comment::class => 'Comment',
                        User::class => 'User',
                        Report::class => 'Report',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
