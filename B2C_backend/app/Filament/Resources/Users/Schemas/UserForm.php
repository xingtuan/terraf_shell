<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Filament\Support\PanelAccess;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Full name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('username')
                                    ->required()
                                    ->alphaDash()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Select::make('role')
                                    ->options(UserRole::options())
                                    ->default(UserRole::Creator->value)
                                    ->required()
                                    ->visible(fn (): bool => PanelAccess::isAdmin()),
                                Select::make('account_status')
                                    ->options(AccountStatus::options())
                                    ->default(AccountStatus::Active->value)
                                    ->required()
                                    ->visible(fn (): bool => PanelAccess::isAdmin()),
                                Toggle::make('community_auto_approve')
                                    ->label('Direct community approval')
                                    ->helperText('When the moderation policy allows trusted users, this account will be approved automatically.')
                                    ->visible(fn (): bool => PanelAccess::isAdmin()),
                                TextInput::make('password')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->minLength(8)
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Profile')
                    ->relationship('profile')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('avatar_path')
                                    ->label('Avatar')
                                    ->image()
                                    ->disk((string) config('community.uploads.disk'))
                                    ->directory('avatars')
                                    ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public')
                                    ->imagePreviewHeight('140')
                                    ->avatar(),
                                Textarea::make('bio')
                                    ->rows(5)
                                    ->columnSpanFull(),
                                TextInput::make('school_or_company')
                                    ->maxLength(150),
                                TextInput::make('region')
                                    ->maxLength(255),
                                TextInput::make('portfolio_url')
                                    ->url()
                                    ->maxLength(255),
                                Toggle::make('open_to_collab')
                                    ->label('Open to collaboration'),
                            ]),
                    ]),
            ]);
    }
}
