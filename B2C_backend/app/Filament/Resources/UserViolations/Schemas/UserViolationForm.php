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
                Section::make(__('admin.ui.violation'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->label(__('admin.ui.user'))
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                Select::make('report_id')
                                    ->relationship('report', 'id')
                                    ->searchable()
                                    ->preload()
                                    ->label(__('admin.ui.linked_report'))
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                Select::make('subject_type')
                                    ->options([
                                        'post' => 'Post',
                                        'comment' => 'Comment',
                                        'user' => 'User',
                                    ])
                                    ->label(__('admin.ui.subject_type'))
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                TextInput::make('subject_id')
                                    ->numeric()
                                    ->label(__('admin.ui.subject_id'))
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                Select::make('type')
                                    ->label(__('admin.fields.type'))
                                    ->options(UserViolationType::options())
                                    ->required()
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                Select::make('severity')
                                    ->label(__('admin.ui.severity'))
                                    ->options(UserViolationSeverity::options())
                                    ->required()
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                Select::make('status')
                                    ->label(__('admin.fields.status'))
                                    ->options(UserViolationStatus::options())
                                    ->required()
                                    ->default(UserViolationStatus::Open->value),
                                Textarea::make('reason')
                                    ->label(__('admin.fields.reason'))
                                    ->rows(4)
                                    ->columnSpanFull()
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                Textarea::make('resolution_note')
                                    ->label(__('admin.ui.resolution_note'))
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
