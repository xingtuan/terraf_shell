<?php

namespace App\Filament\Resources\AdminActionLogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AdminActionLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('actor_user_id')
                    ->numeric(),
                Select::make('target_user_id')
                    ->label(__('admin.ui.user'))
                    ->relationship('targetUser', 'name'),
                TextInput::make('subject_type'),
                TextInput::make('subject_id')
                    ->numeric(),
                TextInput::make('action')
                    ->required(),
                Textarea::make('description')
                    ->label(__('admin.ui.description'))
                    ->columnSpanFull(),
                TextInput::make('metadata'),
            ]);
    }
}
