<?php

namespace App\Filament\Resources\EmailEvents;

use App\Filament\Resources\EmailEvents\Pages\EditEmailEvent;
use App\Filament\Resources\EmailEvents\Pages\ListEmailEvents;
use App\Filament\Support\PanelAccess;
use App\Models\EmailEvent;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Services\Email\EmailCenterDefaults;
use App\Services\Email\EmailDispatchService;
use App\Services\Email\EmailTemplateRenderer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class EmailEventResource extends Resource
{
    protected static ?string $model = EmailEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|\UnitEnum|null $navigationGroup = 'Email Center';

    protected static ?string $navigationLabel = 'Events';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Email Stage')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('category')->required()->maxLength(80),
                            TextInput::make('name')->required()->maxLength(255),
                            TextInput::make('key')->disabled()->dehydrated(false),
                            Toggle::make('is_enabled')->label('Enabled'),
                            Select::make('recipient_type')
                                ->options([
                                    'user' => 'User',
                                    'admin' => 'Admin',
                                    'both' => 'Both',
                                    'custom' => 'Custom',
                                ])
                                ->required(),
                            Select::make('template_key')
                                ->options(fn (): array => EmailTemplate::query()
                                    ->where('locale', 'en')
                                    ->orderBy('key')
                                    ->pluck('key', 'key')
                                    ->all())
                                ->searchable()
                                ->required(),
                            TextInput::make('throttle_minutes')->numeric()->minValue(1),
                            Toggle::make('use_queue')->default(true),
                            TagsInput::make('custom_recipients')
                                ->label('Custom recipients')
                                ->columnSpanFull(),
                            TextInput::make('description')
                                ->columnSpanFull()
                                ->maxLength(500),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('category')
                    ->sortable()
                    ->searchable()
                    ->badge(),
                TextColumn::make('name')
                    ->searchable()
                    ->description(fn (EmailEvent $record): string => $record->key),
                IconColumn::make('is_enabled')
                    ->label('Enabled')
                    ->boolean(),
                ToggleColumn::make('is_enabled')
                    ->label('Toggle'),
                TextColumn::make('recipient_type')
                    ->badge(),
                TextColumn::make('template_key')
                    ->label('Template')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('last_status')
                    ->label('Last sent status')
                    ->state(fn (EmailEvent $record): string => $record->lastLog()?->status ?? '-')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        EmailLog::STATUS_SENT => 'success',
                        EmailLog::STATUS_FAILED => 'danger',
                        EmailLog::STATUS_SKIPPED => 'gray',
                        EmailLog::STATUS_QUEUED => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(fn (): array => EmailEvent::query()
                        ->select('category')
                        ->distinct()
                        ->orderBy('category')
                        ->pluck('category', 'category')
                        ->all()),
                SelectFilter::make('is_enabled')
                    ->label('Enabled')
                    ->options([
                        1 => 'Enabled',
                        0 => 'Disabled',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('sendSample')
                    ->label('Send sample')
                    ->form([
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->default(fn (): ?string => PanelAccess::user()?->email),
                    ])
                    ->action(function (EmailEvent $record, array $data): void {
                        $payload = app(EmailTemplateRenderer::class)->samplePayload($record->key);
                        $log = app(EmailDispatchService::class)->sendEvent($record->key, $payload, [
                            'to' => [$data['email']],
                            'sync' => true,
                            'force' => true,
                            'idempotency_key' => 'sample:'.$record->key.':'.now()->timestamp,
                        ]);

                        Notification::make()
                            ->title('Sample email logged.')
                            ->body("Log #{$log?->id}: {$log?->status}")
                            ->success()
                            ->send();
                    }),
                Action::make('resetDefaults')
                    ->label('Reset defaults')
                    ->requiresConfirmation()
                    ->action(function (EmailEvent $record): void {
                        $default = EmailCenterDefaults::eventByKey($record->key);

                        if ($default !== []) {
                            $record->forceFill([
                                'category' => $default['category'],
                                'name' => $default['name'],
                                'description' => EmailCenterDefaults::description($record->key),
                                'is_enabled' => (bool) ($default['enabled'] ?? false),
                                'recipient_type' => $default['recipient_type'],
                                'template_key' => $record->key,
                                'throttle_minutes' => $default['throttle'] ?? null,
                                'use_queue' => true,
                            ])->save();
                        }

                        Notification::make()
                            ->title('Event defaults restored.')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('enable')
                        ->label('Enable selected')
                        ->action(fn (Collection $records): int => EmailEvent::query()->whereKey($records->modelKeys())->update(['is_enabled' => true])),
                    BulkAction::make('disable')
                        ->label('Disable selected')
                        ->action(fn (Collection $records): int => EmailEvent::query()->whereKey($records->modelKeys())->update(['is_enabled' => false])),
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

    public static function canEdit(Model $record): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailEvents::route('/'),
            'edit' => EditEmailEvent::route('/{record}/edit'),
        ];
    }
}
