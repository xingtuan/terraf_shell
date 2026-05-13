<?php

namespace App\Filament\Resources\UserViolations\Tables;

use App\Enums\UserViolationSeverity;
use App\Enums\UserViolationStatus;
use App\Enums\UserViolationType;
use App\Filament\Resources\UserViolations\Actions\UserViolationActions;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserViolationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('admin.ui.user'))
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => UserViolationType::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => UserViolationType::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('severity')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => UserViolationSeverity::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => UserViolationSeverity::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => UserViolationStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => UserViolationStatus::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('actor.name')
                    ->label(__('admin.ui.recorded_by'))
                    ->placeholder(__('admin.ui.system'))
                    ->toggleable(),
                TextColumn::make('report.id')
                    ->label(__('admin.ui.report'))
                    ->placeholder(__('admin.ui.none'))
                    ->toggleable(),
                TextColumn::make('occurred_at')
                    ->label(__('admin.ui.occurred'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('resolved_at')
                    ->label(__('admin.ui.resolved'))
                    ->dateTime()
                    ->placeholder(__('admin.ui.open'))
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(UserViolationType::options()),
                SelectFilter::make('severity')
                    ->options(UserViolationSeverity::options()),
                SelectFilter::make('status')
                    ->options(UserViolationStatus::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                UserViolationActions::resolveOnly(),
                UserViolationActions::resolveAndRestoreAccount(),
                EditAction::make(),
            ]);
    }
}
