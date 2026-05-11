<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\ContentStatus;
use App\Filament\Support\PanelAccess;
use App\Models\User;
use App\Rules\ExternalSafeUrl;
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
                Section::make(__('admin.ui.post'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('admin.ui.title'))
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
                                    ->label(__('admin.ui.author'))
                                    ->relationship('user', 'name')
                                    ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->name.' (@'.$record->username.')')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('category_id')
                                    ->label(__('admin.ui.category'))
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload(),
                                Select::make('status')
                                    ->label(__('admin.fields.status'))
                                    ->options(ContentStatus::options())
                                    ->default(ContentStatus::Pending->value)
                                    ->required(),
                                Select::make('tags')
                                    ->label(__('admin.ui.tags'))
                                    ->relationship('tags', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload(),
                                Toggle::make('is_pinned')
                                    ->label(__('admin.ui.pinned'))
                                    ->visible(fn (): bool => PanelAccess::isAdmin()),
                                Toggle::make('is_featured')
                                    ->label(__('admin.ui.featured'))
                                    ->visible(fn (): bool => PanelAccess::isAdmin()),
                                Toggle::make('is_demo_content')
                                    ->label(__('admin.ui.demo_seed_content'))
                                    ->visible(fn (): bool => PanelAccess::isAdmin()),
                                Textarea::make('excerpt')
                                    ->label(__('admin.ui.excerpt'))
                                    ->rows(4)
                                    ->helperText(__('admin.ui.leave_blank_to_generate_an_excerpt_from_the_content'))
                                    ->columnSpanFull(),
                                TextInput::make('funding_url')
                                    ->label(__('admin.ui.funding_url'))
                                    ->url()
                                    ->rule(new ExternalSafeUrl)
                                    ->maxLength(2048)
                                    ->helperText(__('admin.ui.external_http_https_urls_only_javascript_and_data_urls_are_rejected'))
                                    ->placeholder('https://www.gofundme.com/... or https://www.kickstarter.com/...')
                                    ->columnSpanFull(),
                                Textarea::make('content')
                                    ->label(__('admin.ui.content'))
                                    ->required()
                                    ->rows(16)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make(__('admin.ui.images'))
                    ->schema([
                        Repeater::make('images')
                            ->relationship()
                            ->label(__('admin.ui.post_images'))
                            ->addActionLabel(__('admin.ui.add_image'))
                            ->orderColumn('sort_order')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->grid(2)
                            ->schema([
                                FileUpload::make('path')
                                    ->label(__('admin.ui.image'))
                                    ->image()
                                    ->required()
                                    ->disk((string) config('community.uploads.disk'))
                                    ->directory('posts')
                                    ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public')
                                    ->imagePreviewHeight('140'),
                                TextInput::make('alt_text')
                                    ->label(__('admin.ui.alt_text'))
                                    ->maxLength(255),
                            ]),
                    ]),
            ]);
    }
}
