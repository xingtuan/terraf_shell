<?php

namespace App\Filament\Resources\EmailLogs;

use App\Filament\Resources\EmailLogs\Pages\ListEmailLogs;
use App\Filament\Resources\EmailLogs\Pages\ViewEmailLog;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\AdminOptions;
use App\Filament\Support\HasAdminResourceTranslations;
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
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as InfolistSection;
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
    use HasAdminResourceTranslations;

    protected static ?string $model = EmailLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::EmailCenter;

    protected static ?int $navigationSort = 40;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        EmailLog::STATUS_SENT => 'success',
                        EmailLog::STATUS_FAILED => 'danger',
                        EmailLog::STATUS_SKIPPED => 'gray',
                        EmailLog::STATUS_QUEUED => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('event_key')
                    ->label(__('admin.ui.event_key'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('to')
                    ->label(__('admin.ui.to'))
                    ->formatStateUsing(fn ($state): string => self::formatRecipients($state))
                    ->limit(50)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('to', 'like', '%'.$search.'%');
                    }),
                TextColumn::make('subject')
                    ->label(__('admin.ui.subject'))
                    ->limit(70)
                    ->searchable(),
                TextColumn::make('related_type')
                    ->label(__('admin.ui.related_model'))
                    ->formatStateUsing(fn (?string $state, EmailLog $record): string => $state ? class_basename($state).' #'.$record->related_id : '-'),
                TextColumn::make('error_message')
                    ->label(__('admin.ui.error'))
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('sent_at')
                    ->label(__('admin.ui.sent_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(fn (): array => AdminOptions::emailLogStatuses()),
                SelectFilter::make('event_key')
                    ->label(__('admin.ui.event_key'))
                    ->options(fn (): array => EmailEvent::query()->orderBy('key')->pluck('key', 'key')->all())
                    ->searchable(),
                SelectFilter::make('category')
                    ->label(__('admin.ui.category'))
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
                        DatePicker::make('from')->label(__('admin.ui.from')),
                        DatePicker::make('until')->label(__('admin.ui.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $builder, string $date): Builder => $builder->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $builder, string $date): Builder => $builder->whereDate('created_at', '<=', $date))),
                Filter::make('recipient')
                    ->schema([
                        TextInput::make('email')->label(__('admin.fields.email'))->email(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['email'] ?? null, fn (Builder $builder, string $email): Builder => $builder->where('to', 'like', '%'.$email.'%'))),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('retry')
                    ->label(__('admin.ui.retry'))
                    ->visible(fn (EmailLog $record): bool => $record->status === EmailLog::STATUS_FAILED)
                    ->requiresConfirmation()
                    ->action(fn (EmailLog $record): EmailLog => app(EmailDispatchService::class)->retry($record)),
                Action::make('markIgnored')
                    ->label(__('admin.ui.mark_ignored'))
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
            InfolistSection::make(__('admin.ui.delivery'))
                ->schema([
                    TextEntry::make('status')->label(__('admin.fields.status'))->badge(),
                    TextEntry::make('event_key')->label(__('admin.ui.event_key')),
                    TextEntry::make('template_key')->label(__('admin.ui.template_key')),
                    TextEntry::make('locale')->label(__('admin.ui.locale')),
                    TextEntry::make('mailer')->label(__('admin.ui.mailer')),
                    TextEntry::make('subject')->label(__('admin.ui.subject'))->columnSpanFull(),
                    TextEntry::make('skip_reason')->label(__('admin.ui.skip_reason'))->placeholder('-'),
                    TextEntry::make('error_message')->label(__('admin.ui.error'))->placeholder('-')->columnSpanFull(),
                    TextEntry::make('queued_at')->label(__('admin.ui.queued_at'))->dateTime()->placeholder('-'),
                    TextEntry::make('sent_at')->label(__('admin.ui.sent_at'))->dateTime()->placeholder('-'),
                    TextEntry::make('failed_at')->label(__('admin.ui.failed_at'))->dateTime()->placeholder('-'),
                ])
                ->columns(3),
            InfolistSection::make(__('admin.ui.recipients'))
                ->schema([
                    KeyValueEntry::make('to')->label(__('admin.ui.to')),
                    KeyValueEntry::make('cc')->label(__('admin.ui.cc')),
                    KeyValueEntry::make('bcc')->label(__('admin.ui.bcc')),
                ]),
            InfolistSection::make(__('admin.ui.rendered_content'))
                ->schema([
                    TextEntry::make('rendered_subject')
                        ->label(__('admin.ui.rendered_subject'))
                        ->state(fn (EmailLog $record): ?string => data_get($record->payload, '_rendered.subject')),
                    TextEntry::make('rendered_text')
                        ->label(__('admin.ui.body'))
                        ->state(fn (EmailLog $record): ?string => data_get($record->payload, '_rendered.text') ?: strip_tags((string) data_get($record->payload, '_rendered.html')))
                        ->columnSpanFull(),
                ]),
            InfolistSection::make(__('admin.ui.payload'))
                ->schema([
                    KeyValueEntry::make('payload')->label(__('admin.ui.payload'))->columnSpanFull(),
                ]),
        ]);
    }

    public static function canViewAny(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function formatRecipients(mixed $recipients): string
    {
        return collect(is_array($recipients) ? $recipients : [$recipients])
            ->map(fn (mixed $recipient): ?string => self::recipientEmail($recipient))
            ->filter()
            ->implode(', ');
    }

    private static function recipientEmail(mixed $recipient): ?string
    {
        if (is_string($recipient)) {
            return trim($recipient) ?: null;
        }

        if (! is_array($recipient)) {
            return null;
        }

        $email = $recipient['email'] ?? $recipient['address'] ?? null;

        if (! is_string($email) && count($recipient) === 1) {
            $email = reset($recipient);
        }

        return is_scalar($email) && trim((string) $email) !== ''
            ? trim((string) $email)
            : null;
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
