<?php

namespace App\Filament\Resources\UserViolations\Tables;

use App\Enums\UserViolationSeverity;
use App\Enums\UserViolationStatus;
use App\Enums\UserViolationType;
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
                    ->label('User')
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
                    ->label('Recorded by')
                    ->placeholder('System')
                    ->toggleable(),
                TextColumn::make('report.id')
                    ->label('Report')
                    ->placeholder('None')
                    ->toggleable(),
                TextColumn::make('occurred_at')
                    ->label('Occurred')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('resolved_at')
                    ->label('Resolved')
                    ->dateTime()
                    ->placeholder('Open')
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
                EditAction::make(),
            ]);
    }
}
