<?php

namespace App\Filament\Resources\UserViolations\Schemas;

use App\Enums\UserViolationSeverity;
use App\Enums\UserViolationStatus;
use App\Enums\UserViolationType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserViolationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Violation')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                Select::make('report_id')
                                    ->relationship('report', 'id')
                                    ->searchable()
                                    ->preload()
                                    ->label('Linked report')
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                Select::make('subject_type')
                                    ->options([
                                        'post' => 'Post',
                                        'comment' => 'Comment',
                                        'user' => 'User',
                                    ])
                                    ->label('Subject type')
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                TextInput::make('subject_id')
                                    ->numeric()
                                    ->label('Subject ID')
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                Select::make('type')
                                    ->options(UserViolationType::options())
                                    ->required()
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                Select::make('severity')
                                    ->options(UserViolationSeverity::options())
                                    ->required()
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                Select::make('status')
                                    ->options(UserViolationStatus::options())
                                    ->required()
                                    ->default(UserViolationStatus::Open->value),
                                Textarea::make('reason')
                                    ->rows(4)
                                    ->columnSpanFull()
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                Textarea::make('resolution_note')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
