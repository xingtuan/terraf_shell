<?php

namespace App\Filament\Pages;

use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Models\AdminActionLog;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as DbSchema;

class DemoCleanup extends Page
{
    public ?array $data = [];

    protected static ?string $title = null;

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::SystemSettings;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trash';

    protected static ?int $navigationSort = 100;

    protected static ?string $slug = 'demo-cleanup';

    public static function canAccess(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.pages.demo_cleanup');
    }

    public function getTitle(): string
    {
        return __('admin.pages.demo_cleanup');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make(__('admin.demo_cleanup.sections.detected'))
                ->description(__('admin.demo_cleanup.help.safe_scope'))
                ->schema([
                    Grid::make(3)->schema([
                        Placeholder::make('demo_posts')
                            ->label(__('admin.demo_cleanup.fields.demo_posts'))
                            ->content(fn (): string => (string) $this->demoPostCount()),
                        Placeholder::make('demo_comments')
                            ->label(__('admin.demo_cleanup.fields.demo_comments'))
                            ->content(fn (): string => (string) $this->demoCommentCount()),
                        Placeholder::make('demo_orders')
                            ->label(__('admin.demo_cleanup.fields.demo_orders'))
                            ->content('0'),
                        Placeholder::make('demo_leads')
                            ->label(__('admin.demo_cleanup.fields.demo_leads'))
                            ->content('0'),
                        Placeholder::make('demo_media')
                            ->label(__('admin.demo_cleanup.fields.demo_media'))
                            ->content('0'),
                        Placeholder::make('demo_users')
                            ->label(__('admin.demo_cleanup.fields.demo_users'))
                            ->content('0'),
                    ]),
                ]),
        ]);
    }

    public function cleanupCommunityDemoContent(): void
    {
        $postIds = $this->demoPostIds();
        $counts = [
            'posts' => count($postIds),
            'comments' => $this->demoCommentCount(),
        ];

        DB::transaction(function () use ($postIds, $counts): void {
            if ($postIds !== []) {
                Post::query()->whereKey($postIds)->delete();
            }

            if (DbSchema::hasTable('admin_action_logs')) {
                AdminActionLog::query()->create([
                    'actor_user_id' => PanelAccess::user()?->id,
                    'action' => 'demo_cleanup.community',
                    'description' => __('admin.ui.cleaned_marked_demo_community_content'),
                    'metadata' => $counts,
                ]);
            }
        });

        Notification::make()
            ->title(__('admin.demo_cleanup.messages.cleaned'))
            ->success()
            ->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->footer([Actions::make([
                    Action::make('cleanupCommunityDemoContent')
                        ->label(__('admin.demo_cleanup.actions.cleanup_community'))
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action('cleanupCommunityDemoContent'),
                ])]),
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function demoPostIds(): array
    {
        return DbSchema::hasColumn('posts', 'is_demo_content')
            ? Post::query()->where('is_demo_content', true)->pluck('id')->map(fn ($id): int => (int) $id)->all()
            : [];
    }

    private function demoPostCount(): int
    {
        return count($this->demoPostIds());
    }

    private function demoCommentCount(): int
    {
        $ids = $this->demoPostIds();

        return $ids === [] ? 0 : (int) DB::table('comments')->whereIn('post_id', $ids)->count();
    }
}
