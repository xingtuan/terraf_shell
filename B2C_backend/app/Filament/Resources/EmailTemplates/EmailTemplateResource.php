<?php

namespace App\Filament\Resources\EmailTemplates;

use App\Filament\Resources\EmailTemplates\Pages\EditEmailTemplate;
use App\Filament\Resources\EmailTemplates\Pages\ListEmailTemplates;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\HasAdminResourceTranslations;
use App\Filament\Support\PanelAccess;
use App\Models\EmailTemplate;
use App\Services\Email\EmailCenterDefaults;
use App\Services\Email\EmailDispatchService;
use App\Services\Email\EmailTemplateRenderer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
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
use Illuminate\Support\HtmlString;

class EmailTemplateResource extends Resource
{
    use HasAdminResourceTranslations;

    protected static ?string $model = EmailTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::EmailCenter;

    protected static ?string $navigationLabel = 'Templates';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.ui.template'))
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('key')->disabled()->dehydrated(false),
                            Select::make('locale')
                                ->options([
                                    'en' => 'EN',
                                    'zh' => 'ZH',
                                    'ko' => 'KO',
                                ])
                                ->disabled()
                                ->dehydrated(false),
                            Toggle::make('is_active'),
                            TextInput::make('name')->required()->maxLength(255),
                            TextInput::make('subject')->required()->maxLength(255)->columnSpan(2),
                            TextInput::make('preheader')->maxLength(255)->columnSpanFull(),
                        ]),
                ]),
            Section::make(__('admin.ui.body'))
                ->schema([
                    Textarea::make('html_body')
                        ->label(__('admin.ui.html_body'))
                        ->rows(16)
                        ->required()
                        ->columnSpanFull(),
                    Textarea::make('text_body')
                        ->label(__('admin.ui.plain_text_body'))
                        ->rows(10)
                        ->columnSpanFull(),
                ]),
            Section::make(__('admin.ui.variables'))
                ->schema([
                    TagsInput::make('available_variables')
                        ->disabled()
                        ->dehydrated(false),
                    Placeholder::make('variable_help')
                        ->label(__('admin.ui.safe_placeholders'))
                        ->content(fn (?EmailTemplate $record): HtmlString => new HtmlString(
                            __('admin.email.template_placeholder_help', ['example' => '<code>{{ user.name }}</code>'])
                            .'<br>'.__('admin.ui.available').': '.e(collect($record?->available_variables ?? [])->implode(', '))
                        )),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('key')
            ->columns([
                TextColumn::make('key')
                    ->searchable()
                    ->copyable()
                    ->description(fn (EmailTemplate $record): string => $record->name),
                TextColumn::make('locale')
                    ->badge()
                    ->sortable(),
                TextColumn::make('subject')
                    ->searchable()
                    ->limit(60),
                TextColumn::make('version')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('locale')
                    ->options([
                        'en' => 'EN',
                        'zh' => 'ZH',
                        'ko' => 'KO',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('preview')
                    ->modalHeading(__('admin.ui.template_preview'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('admin.actions.close'))
                    ->infolist(function (EmailTemplate $record): array {
                        $rendered = app(EmailTemplateRenderer::class)->render([
                            'subject' => $record->subject,
                            'html_body' => $record->html_body,
                            'text_body' => $record->text_body,
                        ], app(EmailTemplateRenderer::class)->samplePayload($record->key));

                        return [
                            TextEntry::make('subject')
                                ->state($rendered['subject']),
                            TextEntry::make('body')
                                ->state($rendered['text'] ?: strip_tags($rendered['html']))
                                ->columnSpanFull(),
                        ];
                    }),
                Action::make('sendTest')
                    ->label(__('admin.ui.send_test'))
                    ->form([
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->default(fn (): ?string => PanelAccess::user()?->email),
                    ])
                    ->action(function (EmailTemplate $record, array $data): void {
                        $log = app(EmailDispatchService::class)->sendEvent($record->key, app(EmailTemplateRenderer::class)->samplePayload($record->key), [
                            'to' => [$data['email']],
                            'locale' => $record->locale,
                            'sync' => true,
                            'force' => true,
                            'idempotency_key' => 'template-test:'.$record->id.':'.now()->timestamp,
                        ]);

                        Notification::make()
                            ->title(__('admin.ui.test_email_logged'))
                            ->body(__('admin.email.test_result', ['id' => $log?->id, 'status' => $log?->status]))
                            ->success()
                            ->send();
                    }),
                Action::make('resetDefault')
                    ->label(__('admin.ui.reset'))
                    ->requiresConfirmation()
                    ->action(function (EmailTemplate $record): void {
                        $record->forceFill([
                            'subject' => EmailCenterDefaults::subject($record->key, $record->locale),
                            'preheader' => 'Notification from {{ app.name }}',
                            'html_body' => EmailCenterDefaults::html($record->key),
                            'text_body' => EmailCenterDefaults::text($record->key),
                            'available_variables' => EmailCenterDefaults::variables($record->key),
                            'updated_by_id' => PanelAccess::user()?->id,
                        ])->save();

                        Notification::make()
                            ->title(__('admin.ui.template_reset'))
                            ->success()
                            ->send();
                    }),
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
            'index' => ListEmailTemplates::route('/'),
            'edit' => EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
