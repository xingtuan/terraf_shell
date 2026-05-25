<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Support\PanelAccess;
use App\Models\Post;
use App\Services\AdminModerationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PendingPostsQueue extends TableWidget
{
    protected static ?string $heading = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Post::query()
                ->with(['user.profile', 'category', 'openSensitiveWordViolation'])
                ->withCount(['reports'])
                ->where('status', ContentStatus::Pending->value)
                ->latest())
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.ui.concept'))
                    ->searchable()
                    ->description(fn (Post $record): string => Str::limit($record->excerpt ?: $record->content, 90))
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label(__('admin.ui.creator'))
                    ->description(fn (Post $record): string => '@'.$record->user->username)
                    ->searchable(['name', 'username']),
                TextColumn::make('category.name')
                    ->label(__('admin.ui.category'))
                    ->badge()
                    ->placeholder(__('admin.ui.uncategorized')),
                TextColumn::make('reports_count')
                    ->label(__('admin.ui.reports'))
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('sensitive_word_flag')
                    ->label(__('admin.ui.review_reason'))
                    ->badge()
                    ->state(function (Post $record): ?string {
                        if ($record->relationLoaded('openSensitiveWordViolation') && $record->openSensitiveWordViolation !== null) {
                            return __('admin.ui.sensitive_word_detected');
                        }

                        return null;
                    })
                    ->color('warning')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label(__('admin.ui.submitted'))
                    ->dateTime()
                    ->since(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('admin.ui.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (Post $record): string => PostResource::getUrl('view', ['record' => $record])),
                $this->statusAction('approve', __('admin.actions.approve'), ContentStatus::Approved->value, 'success'),
                $this->statusAction('reject', __('admin.actions.reject'), ContentStatus::Rejected->value, 'danger'),
                $this->statusAction('hide', __('admin.actions.hide'), ContentStatus::Hidden->value, 'gray'),
            ])
            ->paginated([10])
            ->emptyStateHeading(__('admin.ui.no_pending_concepts'))
            ->emptyStateDescription(__('admin.ui.new_posts_waiting_for_review_will_appear_here'));
    }

    public static function canView(): bool
    {
        return PanelAccess::isStaff();
    }

    private function statusAction(string $name, string $label, string $status, string $color): Action
    {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->schema([
                Textarea::make('reason')
                    ->label(__('admin.ui.moderation_note'))
                    ->rows(3),
            ])
            ->requiresConfirmation()
            ->action(function (Post $record, array $data) use ($status, $label): void {
                app(AdminModerationService::class)->updatePostStatus(
                    $record,
                    $status,
                    PanelAccess::user(),
                    $data['reason'] ?? null,
                );

                Notification::make()
                    ->title(__('admin.ui.concept_updated_to_label', ['label' => $label]))
                    ->success()
                    ->send();
            });
    }

    public function getTableHeading(): ?string
    {
        return __('admin.widgets.pending_posts');
    }
}
