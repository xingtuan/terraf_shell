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

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::EmailCenter;

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
        return __('admin.pages.email_settings_nav');
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
                Section::make('Delivery Status')
                    ->schema([
                        Placeholder::make('sending_enabled')
                            ->label('Email sending')
                            ->content(fn (): string => ($this->data['is_enabled'] ?? false) ? 'Enabled' : 'Disabled'),
                        Placeholder::make('selected_mailer')
                            ->label('Selected provider')
                            ->content(fn (): string => (string) ($this->data['mailer'] ?? config('mail.default'))),
                        Placeholder::make('failed_count')
                            ->label('Failed emails')
                            ->content(fn (): string => number_format(EmailLog::query()->where('status', EmailLog::STATUS_FAILED)->count())),
                        Placeholder::make('last_sent')
                            ->label('Last sent email')
                            ->content(fn (): string => EmailLog::query()->where('status', EmailLog::STATUS_SENT)->latest('sent_at')->first()?->sent_at?->toDateTimeString() ?? 'No sent email logged.'),
                    ])
                    ->columns(4),
                Section::make('Global Delivery')
                    ->schema([
                        Toggle::make('is_enabled')
                            ->label('Enable email sending')
                            ->helperText('When disabled, Email Center creates skipped logs and user-facing actions still succeed.'),
                        Select::make('mailer')
                            ->options(array_combine(MailSettingsService::MAILERS, MailSettingsService::MAILERS))
                            ->required()
                            ->live(),
                        Toggle::make('use_queue')
                            ->label('Send through queue')
                            ->default(true),
                    ])
                    ->columns(3),
                Section::make('SMTP')
                    ->visible(fn (Get $get): bool => $get('mailer') === 'smtp')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('host')->maxLength(255),
                                TextInput::make('port')->numeric()->minValue(1)->maxValue(65535),
                                Select::make('encryption')
                                    ->options([
                                        null => 'None',
                                        'tls' => 'TLS',
                                        'ssl' => 'SSL',
                                    ]),
                                TextInput::make('username')->maxLength(255),
                                TextInput::make('password')
                                    ->password()
                                    ->revealable()
                                    ->maxLength(255)
                                    ->helperText('Leave masked or empty to keep the current password.'),
                                TextInput::make('timeout')->numeric()->minValue(1)->maxValue(120),
                            ]),
                    ]),
                Section::make('Provider Credentials')
                    ->visible(fn (Get $get): bool => in_array($get('mailer'), ['mailgun', 'ses', 'postmark', 'resend'], true))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('api_key')
                                    ->label('API key / secret')
                                    ->password()
                                    ->revealable()
                                    ->helperText('Leave masked or empty to keep the current key.'),
                                TextInput::make('domain')
                                    ->visible(fn (Get $get): bool => $get('mailer') === 'mailgun'),
                                TextInput::make('region')
                                    ->visible(fn (Get $get): bool => $get('mailer') === 'ses'),
                            ]),
                    ]),
                Section::make('Sender Identity')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('from_address')->email()->required(),
                                TextInput::make('from_name')->required()->maxLength(255),
                                TextInput::make('reply_to_address')->email(),
                                TextInput::make('reply_to_name')->maxLength(255),
                            ]),
                    ]),
                Section::make('Admin Recipients')
                    ->schema([
                        TagsInput::make('admin_recipients')
                            ->label('Admin email recipients')
                            ->placeholder('ops@example.com')
                            ->helperText('Used for admin recipient email events. Active admin users are used when this is empty.'),
                    ]),
                Section::make('Test Tools')
                    ->schema([
                        TextInput::make('test_email')
                            ->email()
                            ->label('Send test email to'),
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
                ->title('Enter a valid test email address.')
                ->danger()
                ->send();

            return;
        }

        $log = $emailDispatchService->sendTest($email, PanelAccess::user());
        $this->lastTestResult = "Log #{$log->id}: {$log->status}".($log->error_message ? ' - '.$log->error_message : '');

        $notification = Notification::make()
            ->title($log->status === EmailLog::STATUS_FAILED ? 'Test email failed.' : 'Test email logged.')
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
