<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
        'excerpt',
        'status',
        'is_pinned',
        'is_featured',
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
            'is_pinned' => 'boolean',
            'is_featured' => 'boolean',
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
}
