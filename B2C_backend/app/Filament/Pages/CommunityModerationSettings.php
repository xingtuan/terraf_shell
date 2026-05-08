<?php

namespace App\Filament\Pages;

use App\Enums\CommunitySubmissionPolicy;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Services\CommunityModerationPolicyService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CommunityModerationSettings extends Page
{
    public ?array $data = [];

    protected static ?string $title = null;

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::UsersGovernance;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?int $navigationSort = 45;

    protected static ?string $slug = 'community-moderation-settings';

    public function mount(): void
    {
        $this->fillForm();
    }

    public static function canAccess(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.pages.moderation_settings');
    }

    public function getTitle(): string
    {
        return __('admin.pages.community_moderation_settings');
    }

    public function getSubheading(): ?string
    {
        return __('admin.community_moderation.subheading');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('admin.community_moderation.sections.submission_policy'))
                    ->description(__('admin.community_moderation.help.submission_policy'))
                    ->schema([
                        Select::make('submission_policy')
                            ->label(__('admin.community_moderation.fields.approval_mode'))
                            ->options(CommunitySubmissionPolicy::options())
                            ->live()
                            ->required()
                            ->helperText(fn (?string $state): ?string => $state
                                ? CommunitySubmissionPolicy::from($state)->helperText()
                                : null),
                        Select::make('trusted_user_ids')
                            ->label(__('admin.community_moderation.fields.trusted_users'))
                            ->options(app(CommunityModerationPolicyService::class)->trustedUserOptions())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText(__('admin.community_moderation.help.trusted_users'))
                            ->visible(fn (Get $get): bool => $get('submission_policy') === CommunitySubmissionPolicy::TrustedUsersAutoApprove->value),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        app(CommunityModerationPolicyService::class)->update(
            $data['submission_policy'],
            $data['trusted_user_ids'] ?? [],
        );

        $this->fillForm();

        Notification::make()
            ->title(__('admin.community_moderation.messages.saved'))
            ->success()
            ->send();
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
        ];
    }

    private function fillForm(): void
    {
        $service = app(CommunityModerationPolicyService::class);
        $settings = $service->getSettings();

        $this->form->fill([
            'submission_policy' => $settings->submission_policy instanceof CommunitySubmissionPolicy
                ? $settings->submission_policy->value
                : (string) $settings->submission_policy,
            'trusted_user_ids' => $service->trustedUserIds(),
        ]);
    }
}
