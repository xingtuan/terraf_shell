<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Support\StorageUrl;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\UserViolationStatus;
use App\Enums\UserViolationType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Schema;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'content',
        'content_json',
        'excerpt',
        'funding_url',
        'cover_image_url',
        'cover_image_path',
        'cover_image_disk',
        'reading_time',
        'status',
        'is_pinned',
        'is_featured',
        'is_demo_content',
        'engagement_score',
        'trending_score',
        'views_count',
        'featured_at',
        'featured_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'content_json' => 'array',
            'cover_image_disk' => 'string',
            'is_pinned' => 'boolean',
            'is_featured' => 'boolean',
            'is_demo_content' => 'boolean',
            'reading_time' => 'integer',
            'engagement_score' => 'integer',
            'trending_score' => 'integer',
            'views_count' => 'integer',
            'featured_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function featuredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'featured_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tags')->withTimestamps();
    }

    public function media(): HasMany
    {
        return $this->hasMany(IdeaMedia::class)->orderBy('sort_order')->orderBy('id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PostImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function fundingCampaign(): HasOne
    {
        return $this->hasOne(FundingCampaign::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'target');
    }

    public function moderationLogs(): MorphMany
    {
        return $this->morphMany(ModerationLog::class, 'subject');
    }

    public function violations(): MorphMany
    {
        return $this->morphMany(UserViolation::class, 'subject');
    }

    public function openSensitiveWordViolation(): MorphOne
    {
        return $this->morphOne(UserViolation::class, 'subject')
            ->where('type', UserViolationType::SensitiveWord->value)
            ->where('status', UserViolationStatus::Open->value)
            ->latestOfMany();
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Approved->value);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->approved();
    }

    public function isVisibleTo(?User $viewer): bool
    {
        if ($this->status === ContentStatus::Approved->value) {
            return true;
        }

        if ($viewer === null) {
            return false;
        }

        return $viewer->isAdmin() || $viewer->is($this->user);
    }

    public function coverImageUrl(): ?string
    {
        if (filled($this->cover_image_path)) {
            return StorageUrl::resolve($this->cover_image_path, $this->coverImageDisk());
        }

        if (filled($this->cover_image_url)) {
            return StorageUrl::normalizePublicUrl($this->cover_image_url);
        }

        $isGalleryImage = fn (IdeaMedia $media): bool => $media->isImage() && ! ($media->metadata['is_attachment'] ?? false);

        $image = $this->relationLoaded('media')
            ? $this->media->first($isGalleryImage)
            : $this->media()->ordered()->get()->first($isGalleryImage);

        if ($image instanceof IdeaMedia) {
            return $image->thumbnail_url ?: $image->preview_url ?: $image->url;
        }

        $legacyImage = $this->relationLoaded('images')
            ? $this->images->first()
            : $this->images()->first();

        return $legacyImage?->url;
    }

    public function coverImageDisk(): string
    {
        $disk = trim((string) ($this->cover_image_disk ?? ''));

        if ($disk !== '') {
            return StorageUrl::normalizeDisk($disk);
        }

        if (
            filled($this->cover_image_path)
            && Schema::hasTable('media_files')
            && Schema::hasColumn('media_files', 'disk')
        ) {
            $mediaDisk = MediaFile::query()
                ->where('path', $this->cover_image_path)
                ->value('disk');

            if (filled($mediaDisk)) {
                return StorageUrl::normalizeDisk((string) $mediaDisk);
            }
        }

        if (filled($this->cover_image_url)) {
            $url = (string) $this->cover_image_url;

            if (str_contains($url, '/storage/') || str_contains($url, '/media/files/public/')) {
                return 'public';
            }

            if (str_contains($url, '.blob.core.windows.net/')) {
                return 'azure';
            }
        }

        return StorageUrl::normalizeDisk();
    }

    /**
     * @return array<int, string>
     */
    public function contentImageUrls(): array
    {
        if (! is_array($this->content_json)) {
            return [];
        }

        $urls = [];
        $this->collectContentImageUrls($this->content_json, $urls);

        return array_values(array_unique(array_filter($urls)));
    }

    /**
     * @param  array<mixed>  $node
     * @param  array<int, string>  $urls
     */
    private function collectContentImageUrls(array $node, array &$urls): void
    {
        if (($node['type'] ?? null) === 'image' && is_array($node['attrs'] ?? null)) {
            $src = $node['attrs']['src'] ?? null;

            if (is_string($src) && filled($src)) {
                $urls[] = StorageUrl::normalizePublicUrl($src) ?? $src;
            }
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                $this->collectContentImageUrls($value, $urls);
            }
        }
    }
}
