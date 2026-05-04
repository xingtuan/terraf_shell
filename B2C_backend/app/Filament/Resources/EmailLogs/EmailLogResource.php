<?php

namespace App\Filament\Resources\EmailLogs;

use App\Filament\Resources\EmailLogs\Pages\ListEmailLogs;
use App\Filament\Resources\EmailLogs\Pages\ViewEmailLog;
use App\Filament\Support\PanelAccess;
use App\Models\EmailEvent;
use App\Models\EmailLog;
use App\Services\Email\EmailDispatchService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmailLogResource extends Resource
{
    protected static ?string $model = EmailLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|\UnitEnum|null $navigationGroup = 'Email Center';

    protected static ?string $navigationLabel = 'Logs';

    protected static ?int $navigationSort = 40;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        EmailLog::STATUS_SENT => 'success',
                        EmailLog::STATUS_FAILED => 'danger',
                        EmailLog::STATUS_SKIPPED => 'gray',
                        EmailLog::STATUS_QUEUED => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('event_key')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('to')
                    ->label('To')
                    ->formatStateUsing(fn ($state): string => collect($state ?? [])
                        ->map(fn (array $recipient): string => $recipient['email'] ?? '')
                        ->filter()
                        ->implode(', '))
                    ->limit(50)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('to', 'like', '%'.$search.'%');
                    }),
                TextColumn::make('subject')
                    ->limit(70)
                    ->searchable(),
                TextColumn::make('related_type')
                    ->label('Related model')
                    ->formatStateUsing(fn (?string $state, EmailLog $record): string => $state ? class_basename($state).' #'.$record->related_id : '-'),
                TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        EmailLog::STATUS_QUEUED => 'Queued',
                        EmailLog::STATUS_SENT => 'Sent',
                        EmailLog::STATUS_FAILED => 'Failed',
                        EmailLog::STATUS_SKIPPED => 'Skipped',
                    ]),
                SelectFilter::make('event_key')
                    ->options(fn (): array => EmailEvent::query()->orderBy('key')->pluck('key', 'key')->all())
                    ->searchable(),
                SelectFilter::make('category')
                    ->options(fn (): array => EmailEvent::query()->select('category')->distinct()->orderBy('category')->pluck('category', 'category')->all())
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        $keys = EmailEvent::query()->where('category', $data['value'])->pluck('key');

                        return $query->whereIn('event_key', $keys);
                    }),
                Filter::make('date')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $builder, string $date): Builder => $builder->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $builder, string $date): Builder => $builder->whereDate('created_at', '<=', $date))),
                Filter::make('recipient')
                    ->schema([
                        TextInput::make('email')->email(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['email'] ?? null, fn (Builder $builder, string $email): Builder => $builder->where('to', 'like', '%'.$email.'%'))),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('retry')
                    ->visible(fn (EmailLog $record): bool => $record->status === EmailLog::STATUS_FAILED)
                    ->requiresConfirmation()
                    ->action(fn (EmailLog $record): EmailLog => app(EmailDispatchService::class)->retry($record)),
                Action::make('markIgnored')
                    ->label('Mark ignored')
                    ->visible(fn (EmailLog $record): bool => $record->status === EmailLog::STATUS_FAILED)
                    ->requiresConfirmation()
                    ->action(fn (EmailLog $record): bool => $record->forceFill([
                        'status' => EmailLog::STATUS_SKIPPED,
                        'skip_reason' => 'ignored',
                    ])->save()),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            InfolistSection::make('Delivery')
                ->schema([
                    TextEntry::make('status')->badge(),
                    TextEntry::make('event_key'),
                    TextEntry::make('template_key'),
                    TextEntry::make('locale'),
                    TextEntry::make('mailer'),
                    TextEntry::make('subject')->columnSpanFull(),
                    TextEntry::make('skip_reason')->placeholder('-'),
                    TextEntry::make('error_message')->placeholder('-')->columnSpanFull(),
                    TextEntry::make('queued_at')->dateTime()->placeholder('-'),
                    TextEntry::make('sent_at')->dateTime()->placeholder('-'),
                    TextEntry::make('failed_at')->dateTime()->placeholder('-'),
                ])
                ->columns(3),
            InfolistSection::make('Recipients')
                ->schema([
                    KeyValueEntry::make('to'),
                    KeyValueEntry::make('cc'),
                    KeyValueEntry::make('bcc'),
                ]),
            InfolistSection::make('Rendered Content')
                ->schema([
                    TextEntry::make('rendered_subject')
                        ->state(fn (EmailLog $record): ?string => data_get($record->payload, '_rendered.subject')),
                    TextEntry::make('rendered_text')
                        ->label('Body')
                        ->state(fn (EmailLog $record): ?string => data_get($record->payload, '_rendered.text') ?: strip_tags((string) data_get($record->payload, '_rendered.html')))
                        ->columnSpanFull(),
                ]),
            InfolistSection::make('Payload')
                ->schema([
                    KeyValueEntry::make('payload')->columnSpanFull(),
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

    public static function getPages(): array
    {
        return [
            'index' => ListEmailLogs::route('/'),
            'view' => ViewEmailLog::route('/{record}'),
        ];
    }
}
