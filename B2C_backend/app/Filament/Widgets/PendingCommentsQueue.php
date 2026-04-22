<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Filament\Resources\Comments\CommentResource;
use App\Filament\Support\PanelAccess;
use App\Models\Comment;
use App\Services\AdminModerationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PendingCommentsQueue extends TableWidget
{
    protected static ?string $heading = 'Pending Comments';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Comment::query()
                ->with(['user.profile', 'post'])
                ->withCount(['reports'])
                ->where('status', ContentStatus::Pending->value)
                ->latest())
            ->columns([
                TextColumn::make('content')
                    ->label('Comment')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => Str::limit($state, 110))
                    ->wrap(),
                TextColumn::make('post.title')
                    ->label('Concept')
                    ->description(fn (Comment $record): string => '#'.$record->post_id),
                TextColumn::make('user.name')
                    ->label('Author')
                    ->description(fn (Comment $record): string => '@'.$record->user->username),
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
                    ->url(fn (Comment $record): string => CommentResource::getUrl('view', ['record' => $record])),
                $this->statusAction('approve', 'Approve', ContentStatus::Approved->value, 'success'),
                $this->statusAction('reject', 'Reject', ContentStatus::Rejected->value, 'danger'),
                $this->statusAction('hide', 'Hide', ContentStatus::Hidden->value, 'gray'),
            ])
            ->paginated([10])
            ->emptyStateHeading('No pending comments.')
            ->emptyStateDescription('New comments waiting for review will appear here.');
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
            ->action(function (Comment $record, array $data) use ($status, $label): void {
                app(AdminModerationService::class)->updateCommentStatus(
                    $record,
                    $status,
                    PanelAccess::user(),
                    $data['reason'] ?? null,
                );

                Notification::make()
                    ->title("Comment updated to {$label}.")
                    ->success()
                    ->send();
            });
    }
}
