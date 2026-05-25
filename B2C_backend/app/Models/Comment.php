<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\UserViolationStatus;
use App\Enums\UserViolationType;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'content',
        'status',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
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
