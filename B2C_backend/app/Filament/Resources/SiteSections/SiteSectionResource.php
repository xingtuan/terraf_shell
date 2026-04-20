<?php

namespace App\Filament\Resources\SiteSections;

use App\Filament\Resources\SiteSections\Pages\CreateSiteSection;
use App\Filament\Resources\SiteSections\Pages\EditSiteSection;
use App\Filament\Resources\SiteSections\Pages\ListSiteSections;
use App\Filament\Support\PanelAccess;
use App\Models\SiteSection;
use BackedEnum;
use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SiteSectionResource extends Resource
{
    protected static ?string $model = SiteSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Site Sections';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Section::make('Section Content')
                    ->schema([
                        Select::make('page')
                            ->options(self::pageOptions())
                            ->required(),
                        TextInput::make('section')
                            ->required()
                            ->maxLength(255),
                        Select::make('locale')
                            ->options(self::localeOptions())
                            ->required(),
                        TextInput::make('title')
                            ->maxLength(255),
                        TextInput::make('subtitle')
                            ->maxLength(255),
                        Textarea::make('body')
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),
                Section::make('Actions & Media')
                    ->schema([
                        TextInput::make('cta_label')
                            ->maxLength(255),
                        TextInput::make('cta_url')
                            ->maxLength(255),
                        TextInput::make('image_url')
                            ->maxLength(255),
                        Textarea::make('metadata')
                            ->rows(10)
                            ->helperText('JSON object. Example: {"points":["Compatible with existing compression moulding lines"]}')
                            ->formatStateUsing(
                                fn (mixed $state): ?string => is_array($state)
                                    ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                    : (is_string($state) ? $state : null)
                            )
                            ->dehydrateStateUsing(function (mixed $state): ?array {
                                if (! is_string($state) || trim($state) === '') {
                                    return null;
                                }

                                $decoded = json_decode($state, true);

                                return is_array($decoded) ? $decoded : null;
                            })
                            ->rules([
                                function (string $attribute, mixed $value, Closure $fail): void {
                                    if (! is_string($value) || trim($value) === '') {
                                        return;
                                    }

                                    json_decode($value, true);

                                    if (json_last_error() !== JSON_ERROR_NONE) {
                                        $fail('Metadata must be valid JSON.');
                                    }
                                },
                            ])
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->required()
                            ->default(0),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('page')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('section')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('locale')
                    ->badge()
                    ->sortable(),
                TextColumn::make('title')
                    ->limit(40)
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('page')
                    ->options(self::pageOptions()),
                SelectFilter::make('locale')
                    ->options(self::localeOptions()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canView(Model $record): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canCreate(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSiteSections::route('/'),
            'create' => CreateSiteSection::route('/create'),
            'edit' => EditSiteSection::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function pageOptions(): array
    {
        return [
            'home' => 'home',
            'material' => 'material',
            'store' => 'store',
            'b2b' => 'b2b',
            'community' => 'community',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function localeOptions(): array
    {
        return [
            'en' => 'en',
            'ko' => 'ko',
            'zh' => 'zh',
        ];
    }
}
