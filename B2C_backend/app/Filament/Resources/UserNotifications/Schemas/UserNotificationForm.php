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
                    ->required(),
                TextInput::make('title'),
                Textarea::make('body')
                    ->columnSpanFull(),
                TextInput::make('action_url')
                    ->url(),
                TextInput::make('target_type'),
                TextInput::make('target_id')
                    ->numeric(),
                TextInput::make('data'),
                Toggle::make('is_read')
                    ->required(),
                DateTimePicker::make('read_at'),
            ]);
    }
}
