<?php

namespace App\Filament\Resources\UserNotifications\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('recipient_user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('actor_user_id')
                    ->numeric(),
                TextInput::make('type')
                    ->label(__('admin.fields.type'))
                    ->required(),
                TextInput::make('title')
                    ->label(__('admin.ui.title')),
                Textarea::make('body')
                    ->label(__('admin.ui.body'))
                    ->columnSpanFull(),
                TextInput::make('action_url')
                    ->label(__('admin.ui.action_url'))
                    ->url(),
                TextInput::make('target_type'),
                TextInput::make('target_id')
                    ->numeric(),
                TextInput::make('data'),
                Toggle::make('is_read')
                    ->label(__('admin.ui.is_read'))
                    ->required(),
                DateTimePicker::make('read_at')
                    ->label(__('admin.ui.read_at')),
            ]);
    }
}
