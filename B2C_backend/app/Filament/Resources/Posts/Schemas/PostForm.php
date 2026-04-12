<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\ContentStatus;
use App\Filament\Support\PanelAccess;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Post')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(200)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                                        $set('slug', Str::slug((string) $state));
                                    }),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Select::make('user_id')
                                    ->label('Author')
                                    ->relationship('user', 'name')
                                    ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->name.' (@'.$record->username.')')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('category_id')
                                    ->label('Category')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload(),
                                Select::make('status')
                                    ->options(ContentStatus::options())
                                    ->default(ContentStatus::Pending->value)
                                    ->required(),
                                Select::make('tags')
                                    ->relationship('tags', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload(),
                                Toggle::make('is_pinned')
                                    ->label('Pinned')
                                    ->visible(fn (): bool => PanelAccess::isAdmin()),
                                Toggle::make('is_featured')
                                    ->label('Featured')
                                    ->visible(fn (): bool => PanelAccess::isAdmin()),
                                Textarea::make('excerpt')
                                    ->rows(4)
                                    ->helperText('Leave blank to generate an excerpt from the content.')
                                    ->columnSpanFull(),
                                Textarea::make('content')
                                    ->required()
                                    ->rows(16)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Images')
                    ->schema([
                        Repeater::make('images')
                            ->relationship()
                            ->label('Post images')
                            ->addActionLabel('Add image')
                            ->orderColumn('sort_order')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->grid(2)
                            ->schema([
                                FileUpload::make('path')
                                    ->label('Image')
                                    ->image()
                                    ->required()
                                    ->disk((string) config('community.uploads.disk'))
                                    ->directory('posts')
                                    ->visibility('public')
                                    ->imagePreviewHeight('140'),
                                TextInput::make('alt_text')
                                    ->label('Alt text')
                                    ->maxLength(255),
                            ]),
                    ]),
            ]);
    }
}
