<?php

namespace App\Filament\Pages;

use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Models\EmailLog;
use App\Services\Email\EmailDispatchService;
use App\Services\Email\MailSettingsService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class EmailSettings extends Page
{
    public ?array $data = [];

    public ?string $lastTestResult = null;

    protected static ?string $title = null;

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::SystemSettings;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'email-settings';

    public function mount(MailSettingsService $mailSettingsService): void
    {
        $this->form->fill(array_merge($mailSettingsService->maskedState(), [
            'test_email' => PanelAccess::user()?->email,
        ]));
    }

    public static function canAccess(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.pages.mail_settings_nav');
    }

    public function getTitle(): string
    {
        return __('admin.pages.email_settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('admin.sections.delivery_status'))
                    ->schema([
                        Placeholder::make('sending_enabled')
                            ->label(__('admin.fields.email_sending'))
                            ->content(fn (): string => ($this->data['is_enabled'] ?? false) ? __('admin.system.enabled') : __('admin.system.disabled')),
                        Placeholder::make('selected_mailer')
                            ->label(__('admin.fields.provider'))
                            ->content(fn (): string => (string) ($this->data['mailer'] ?? config('mail.default'))),
                        Placeholder::make('failed_count')
                            ->label(__('admin.fields.failed_emails'))
                            ->content(fn (): string => number_format(EmailLog::query()->where('status', EmailLog::STATUS_FAILED)->count())),
                        Placeholder::make('last_sent')
                            ->label(__('admin.fields.last_sent_email'))
                            ->content(fn (): string => EmailLog::query()->where('status', EmailLog::STATUS_SENT)->latest('sent_at')->first()?->sent_at?->toDateTimeString() ?? __('admin.placeholders.no_sent_email_logged')),
                    ])
                    ->columns(4),
                Section::make(__('admin.sections.email_global_delivery'))
                    ->schema([
                        Toggle::make('is_enabled')
                            ->label(__('admin.fields.enable_email_sending'))
                            ->helperText(__('admin.email.help.disabled_logs')),
                        Select::make('mailer')
                            ->options(array_combine(MailSettingsService::MAILERS, MailSettingsService::MAILERS))
                            ->required()
                            ->live(),
                        Toggle::make('use_queue')
                            ->label(__('admin.fields.send_through_queue'))
                            ->default(true),
                    ])
                    ->columns(3),
                Section::make(__('admin.email.sections.smtp'))
                    ->visible(fn (Get $get): bool => $get('mailer') === 'smtp')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('host')->maxLength(255),
                                TextInput::make('port')->numeric()->minValue(1)->maxValue(65535),
                                Select::make('encryption')
                                    ->options([
                                        null => __('admin.email.encryption.none'),
                                        'tls' => 'TLS',
                                        'ssl' => 'SSL',
                                    ]),
                                TextInput::make('username')->maxLength(255),
                                TextInput::make('password')
                                    ->password()
                                    ->revealable()
                                    ->maxLength(255)
                                    ->helperText(__('admin.email.help.keep_password')),
                                TextInput::make('timeout')->numeric()->minValue(1)->maxValue(120),
                            ]),
                    ]),
                Section::make(__('admin.sections.email_provider_credentials'))
                    ->visible(fn (Get $get): bool => in_array($get('mailer'), ['mailgun', 'ses', 'postmark', 'resend'], true))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('api_key')
                                    ->label(__('admin.fields.api_key_secret'))
                                    ->password()
                                    ->revealable()
                                    ->helperText(__('admin.email.help.keep_key')),
                                TextInput::make('domain')
                                    ->visible(fn (Get $get): bool => $get('mailer') === 'mailgun'),
                                TextInput::make('region')
                                    ->visible(fn (Get $get): bool => $get('mailer') === 'ses'),
                            ]),
                    ]),
                Section::make(__('admin.sections.email_sender_identity'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('from_address')->label(__('admin.fields.from_address'))->email()->required(),
                                TextInput::make('from_name')->label(__('admin.fields.from_name'))->required()->maxLength(255),
                                TextInput::make('reply_to_address')->label(__('admin.fields.reply_to_address'))->email(),
                                TextInput::make('reply_to_name')->label(__('admin.fields.reply_to_name'))->maxLength(255),
                            ]),
                    ]),
                Section::make(__('admin.sections.email_admin_recipients'))
                    ->schema([
                        TagsInput::make('admin_recipients')
                            ->label(__('admin.email.fields.admin_recipients'))
                            ->placeholder('ops@example.com')
                            ->helperText(__('admin.email.help.admin_recipients')),
                    ]),
                Section::make(__('admin.sections.email_test_tools'))
                    ->schema([
                        TextInput::make('test_email')
                            ->email()
                            ->label(__('admin.fields.send_test_email_to')),
                    ]),
            ]);
    }

    public function save(MailSettingsService $mailSettingsService): void
    {
        $state = $this->form->getState();
        $state = Arr::except($state, ['test_email']);

        if (($state['password'] ?? null) === '********' || blank($state['password'] ?? null)) {
            unset($state['password']);
        }

        if (($state['api_key'] ?? null) === '********' || blank($state['api_key'] ?? null)) {
            unset($state['api_key']);
        }

        $mailSettingsService->save($state, PanelAccess::user());
        $this->form->fill(array_merge($mailSettingsService->maskedState(), [
            'test_email' => $this->data['test_email'] ?? PanelAccess::user()?->email,
        ]));

        Notification::make()
            ->title(__('admin.notifications.saved'))
            ->success()
            ->send();
    }

    public function sendTest(EmailDispatchService $emailDispatchService): void
    {
        $email = (string) ($this->data['test_email'] ?? '');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Notification::make()
                ->title(__('admin.notifications.test_email_invalid'))
                ->danger()
                ->send();

            return;
        }

        $log = $emailDispatchService->sendTest($email, PanelAccess::user());
        $this->lastTestResult = __('admin.email.test_result', ['id' => $log->id, 'status' => $log->status]).($log->error_message ? ' - '.$log->error_message : '');

        $notification = Notification::make()
            ->title($log->status === EmailLog::STATUS_FAILED ? __('admin.notifications.test_email_failed') : __('admin.notifications.test_email_logged'))
            ->body($this->lastTestResult);

        $log->status === EmailLog::STATUS_FAILED
            ? $notification->danger()->send()
            : $notification->success()->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([
                EmbeddedSchema::make('form'),
            ])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make($this->getFormActions()),
                ]),
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin.actions.save_settings'))
                ->submit('save'),
            Action::make('sendTest')
                ->label(__('admin.actions.send_test_email'))
                ->action('sendTest'),
        ];
    }
}
