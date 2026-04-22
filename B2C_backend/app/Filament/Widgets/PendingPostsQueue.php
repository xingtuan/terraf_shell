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
    protected static ?string $heading = 'Pending Concepts';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Post::query()
                ->with(['user.profile', 'category'])
                ->withCount(['reports'])
                ->where('status', ContentStatus::Pending->value)
                ->latest())
            ->columns([
                TextColumn::make('title')
                    ->label('Concept')
                    ->searchable()
                    ->description(fn (Post $record): string => Str::limit($record->excerpt ?: $record->content, 90))
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label('Creator')
                    ->description(fn (Post $record): string => '@'.$record->user->username)
                    ->searchable(['name', 'username']),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->placeholder('Uncategorized'),
                TextColumn::make('reports_count')
                    ->label('Reports')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->since(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Post $record): string => PostResource::getUrl('view', ['record' => $record])),
                $this->statusAction('approve', 'Approve', ContentStatus::Approved->value, 'success'),
                $this->statusAction('reject', 'Reject', ContentStatus::Rejected->value, 'danger'),
                $this->statusAction('hide', 'Hide', ContentStatus::Hidden->value, 'gray'),
            ])
            ->paginated([10])
            ->emptyStateHeading('No pending concepts.')
            ->emptyStateDescription('New posts waiting for review will appear here.');
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
                    ->label('Moderation note')
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
                    ->title("Concept updated to {$label}.")
                    ->success()
                    ->send();
            });
    }
}
