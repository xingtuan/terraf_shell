<?php

namespace App\Filament\Resources\Posts;

use App\Enums\ContentStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Pages\ViewPost;
use App\Filament\Resources\Posts\Schemas\PostForm;
use App\Filament\Resources\Posts\Schemas\PostInfolist;
use App\Filament\Resources\Posts\Tables\PostsTable;
use App\Filament\Support\PanelAccess;
use App\Models\Post;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Community';

    protected static ?string $navigationLabel = 'Concepts';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return PostForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PostInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user.profile', 'category', 'tags', 'images', 'media', 'fundingCampaign', 'featuredBy'])
            ->withCount(['reports']);
    }

    public static function canViewAny(): bool
    {
        return PanelAccess::isStaff();
    }

    public static function canView(Model $record): bool
    {
        return PanelAccess::isStaff();
    }

    public static function canCreate(): bool
    {
        return PanelAccess::isStaff();
    }

    public static function canEdit(Model $record): bool
    {
        return PanelAccess::isStaff();
    }

    public static function canDelete(Model $record): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function normalizeFormData(array $data, ?Post $record = null): array
    {
        $data['slug'] = static::generateUniqueSlug(
            (string) ($data['slug'] ?? $data['title'] ?? $record?->title ?? 'post'),
            $record?->id,
        );

        $data['excerpt'] = filled($data['excerpt'] ?? null)
            ? $data['excerpt']
            : Str::limit(strip_tags((string) ($data['content'] ?? $record?->content ?? '')), 180);

        if (($data['status'] ?? $record?->status) === ContentStatus::Approved->value) {
            $data['published_at'] = $data['published_at'] ?? $record?->published_at ?? now();
        } else {
            $data['published_at'] = null;
        }

        if (! PanelAccess::isAdmin()) {
            unset($data['is_pinned'], $data['is_featured']);
        }

        return $data;
    }

    public static function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        $slug = filled($base) ? $base : 'post';
        $original = $slug;
        $counter = 2;

        while (
            Post::query()
                ->when($ignoreId !== null, fn (Builder $query): Builder => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'view' => ViewPost::route('/{record}'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
