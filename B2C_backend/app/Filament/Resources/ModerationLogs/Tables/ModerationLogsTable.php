<?php

namespace App\Filament\Resources\ModerationLogs\Tables;

use App\Filament\Resources\ModerationLogs\ModerationLogResource;
use App\Models\Comment;
use App\Models\ModerationLog;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ModerationLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('actor.name')
                    ->label(__('admin.ui.actor'))
                    ->default('System')
                    ->searchable(),
                TextColumn::make('action')
                    ->badge()
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label(__('admin.ui.subject_type'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ModerationLogResource::subjectTypeLabel($state))
                    ->color('gray'),
                TextColumn::make('subject_id')
                    ->label(__('admin.ui.subject_id'))
                    ->sortable(),
                TextColumn::make('subject_summary')
                    ->label(__('admin.ui.subject'))
                    ->state(fn (ModerationLog $record): string => ModerationLogResource::subjectSummary($record))
                    ->limit(60),
                TextColumn::make('targetUser.name')
                    ->label(__('admin.ui.target_user'))
                    ->placeholder(__('admin.ui.no_user_target_2'))
                    ->toggleable(),
                TextColumn::make('report_id')
                    ->label(__('admin.ui.report_id'))
                    ->placeholder(__('admin.ui.no_linked_report_2'))
                    ->toggleable(),
                TextColumn::make('reason')
                    ->limit(60)
                    ->default('No reason provided.'),
                TextColumn::make('created_at')
                    ->label(__('admin.ui.created'))
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
                SelectFilter::make('actor_user_id')
                    ->relationship('actor', 'name')
                    ->label(__('admin.ui.actor'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('target_user_id')
                    ->relationship('targetUser', 'name')
                    ->label(__('admin.ui.target_user'))
                    ->searchable()
                    ->preload(),
                Filter::make('action')
                    ->schema([
                        TextInput::make('action')
                            ->label(__('admin.ui.action')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['action'] ?? null),
                        fn (Builder $builder): Builder => $builder->where('action', 'like', '%'.trim((string) $data['action']).'%')
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('viewSubject')
                    ->label(__('admin.ui.view_subject'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ModerationLog $record): ?string => ModerationLogResource::subjectAdminUrl($record))
                    ->visible(fn (ModerationLog $record): bool => filled(ModerationLogResource::subjectAdminUrl($record))),
            ]);
    }
}
