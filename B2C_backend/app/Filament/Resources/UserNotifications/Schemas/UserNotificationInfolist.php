<?php

namespace App\Filament\Resources\UserNotifications\Schemas;

use App\Enums\NotificationType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserNotificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Notification')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('recipient.name')
                                    ->label('Recipient'),
                                TextEntry::make('actor.name')
                                    ->label('Actor')
                                    ->placeholder('System'),
                                TextEntry::make('type')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => NotificationType::tryFrom($state)?->label() ?? $state)
                                    ->color(fn (string $state): string => NotificationType::tryFrom($state)?->color() ?? 'gray'),
                                IconEntry::make('is_read')
                                    ->label('Read')
                                    ->boolean(),
                                TextEntry::make('title')
                                    ->placeholder('No title.')
                                    ->columnSpanFull(),
                                TextEntry::make('body')
                                    ->placeholder('No body.')
                                    ->columnSpanFull(),
                                TextEntry::make('action_url')
                                    ->placeholder('No action URL.')
                                    ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                                    ->openUrlInNewTab()
                                    ->columnSpanFull(),
                                TextEntry::make('target_type')
                                    ->placeholder('No target.'),
                                TextEntry::make('target_id')
                                    ->placeholder('No target.'),
                                TextEntry::make('read_at')
                                    ->label('Read at')
                                    ->dateTime()
                                    ->placeholder('Unread.'),
                                TextEntry::make('created_at')
                                    ->label('Created at')
                                    ->dateTime(),
                                TextEntry::make('data')
                                    ->state(fn ($record): string => json_encode($record->data ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
