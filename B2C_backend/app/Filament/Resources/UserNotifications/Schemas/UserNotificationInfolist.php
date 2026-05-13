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
                Section::make(__('admin.ui.notification'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('recipient.name')
                                    ->label(__('admin.ui.recipient')),
                                TextEntry::make('actor.name')
                                    ->label(__('admin.ui.actor'))
                                    ->placeholder(__('admin.ui.system')),
                                TextEntry::make('type')
                                    ->label(__('admin.fields.type'))
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => NotificationType::tryFrom($state)?->label() ?? $state)
                                    ->color(fn (string $state): string => NotificationType::tryFrom($state)?->color() ?? 'gray'),
                                IconEntry::make('is_read')
                                    ->label(__('admin.ui.read'))
                                    ->boolean(),
                                TextEntry::make('title')
                                    ->label(__('admin.ui.title'))
                                    ->placeholder(__('admin.ui.no_title'))
                                    ->columnSpanFull(),
                                TextEntry::make('body')
                                    ->label(__('admin.ui.body'))
                                    ->placeholder(__('admin.ui.no_body'))
                                    ->columnSpanFull(),
                                TextEntry::make('action_url')
                                    ->label(__('admin.ui.action_url'))
                                    ->placeholder(__('admin.ui.no_action_url'))
                                    ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                                    ->openUrlInNewTab()
                                    ->columnSpanFull(),
                                TextEntry::make('target_type')
                                    ->label(__('admin.ui.target_type'))
                                    ->placeholder(__('admin.ui.no_target')),
                                TextEntry::make('target_id')
                                    ->label(__('admin.ui.target_id'))
                                    ->placeholder(__('admin.ui.no_target')),
                                TextEntry::make('read_at')
                                    ->label(__('admin.ui.read_at'))
                                    ->dateTime()
                                    ->placeholder(__('admin.ui.unread')),
                                TextEntry::make('created_at')
                                    ->label(__('admin.ui.created_at'))
                                    ->dateTime(),
                                TextEntry::make('data')
                                    ->label(__('admin.ui.data'))
                                    ->state(fn ($record): string => json_encode($record->data ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
