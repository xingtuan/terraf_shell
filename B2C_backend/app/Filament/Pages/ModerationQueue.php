<?php

namespace App\Filament\Pages;

use App\Enums\ContentStatus;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Filament\Widgets\PendingCommentsQueue;
use App\Filament\Widgets\PendingPostsQueue;
use App\Models\Comment;
use App\Models\Post;
use Filament\Pages\Page;

class ModerationQueue extends Page
{
    protected static ?string $title = null;

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::Community;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 35;

    protected static ?string $slug = 'moderation-queue';

    protected ?string $subheading = null;

    public static function canAccess(): bool
    {
        return PanelAccess::isStaff();
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.pages.moderation_queue');
    }

    public function getTitle(): string
    {
        return __('admin.pages.moderation_queue');
    }

    public function getSubheading(): ?string
    {
        return __('admin.pages.moderation_queue_subheading');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::pendingItemsCount();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::pendingItemsCount() > 0 ? 'warning' : null;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PendingPostsQueue::class,
            PendingCommentsQueue::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    private static function pendingItemsCount(): int
    {
        return Post::query()->where('status', ContentStatus::Pending->value)->count()
            + Comment::query()->where('status', ContentStatus::Pending->value)->count();
    }
}
