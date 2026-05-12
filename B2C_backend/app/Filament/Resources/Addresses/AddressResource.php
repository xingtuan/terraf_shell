<?php

namespace App\Filament\Resources\Addresses;

use App\Filament\Resources\Addresses\Pages\ListAddresses;
use App\Filament\Resources\Addresses\Pages\ViewAddress;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\HasAdminResourceTranslations;
use App\Filament\Support\PanelAccess;
use App\Models\Address;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AddressResource extends Resource
{
    use HasAdminResourceTranslations;

    protected static ?string $model = Address::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::StoreOperations;

    protected static ?int $navigationSort = 61;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('admin.fields.customer'))
                    ->searchable()
                    ->description(fn (Address $record): string => $record->user?->email ?? '-'),
                TextColumn::make('label')
                    ->label(__('admin.fields.address_note'))
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('recipient_name')
                    ->label(__('admin.fields.recipient'))
                    ->searchable(),
                TextColumn::make('city')
                    ->label(__('admin.fields.city'))
                    ->searchable(),
                TextColumn::make('country')
                    ->label(__('admin.fields.country'))
                    ->searchable(),
                TextColumn::make('postal_code')
                    ->label(__('admin.fields.postal_code'))
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_default')
                    ->label(__('admin.fields.is_default'))
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label(__('admin.fields.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_default')
                    ->label(__('admin.fields.is_default')),
                Filter::make('country')
                    ->schema([
                        TextInput::make('country')
                            ->label(__('admin.fields.country')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['country'] ?? null),
                        fn (Builder $builder): Builder => $builder->where('country', 'like', '%'.trim((string) $data['country']).'%')
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.customer'))
                    ->schema([
                        TextEntry::make('user.name')
                            ->label(__('admin.fields.customer')),
                        TextEntry::make('user.email')
                            ->label(__('admin.fields.email'))
                            ->copyable(),
                        TextEntry::make('label')
                            ->label(__('admin.fields.address_note'))
                            ->placeholder('-'),
                        IconEntry::make('is_default')
                            ->label(__('admin.fields.is_default'))
                            ->boolean(),
                    ])
                    ->columns(2),
                Section::make(__('admin.fields.address'))
                    ->schema([
                        TextEntry::make('recipient_name')
                            ->label(__('admin.fields.recipient')),
                        TextEntry::make('phone')
                            ->label(__('admin.fields.phone'))
                            ->placeholder('-'),
                        TextEntry::make('address_line1')
                            ->label(__('admin.fields.address')),
                        TextEntry::make('address_line2')
                            ->label(__('admin.fields.address_line2'))
                            ->placeholder('-'),
                        TextEntry::make('city')
                            ->label(__('admin.fields.city')),
                        TextEntry::make('state_province')
                            ->label(__('admin.fields.region'))
                            ->placeholder('-'),
                        TextEntry::make('postal_code')
                            ->label(__('admin.fields.postal_code'))
                            ->placeholder('-'),
                        TextEntry::make('country')
                            ->label(__('admin.fields.country')),
                    ])
                    ->columns(2),
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
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAddresses::route('/'),
            'view' => ViewAddress::route('/{record}'),
        ];
    }
}
