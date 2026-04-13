<?php

namespace App\Models\Concerns;

use App\Enums\PublishStatus;
use Illuminate\Database\Eloquent\Builder;

trait HasPublishStatus
{
    protected static function bootHasPublishStatus(): void
    {
        static::saving(function ($model): void {
            if ($model->status === PublishStatus::Published->value && blank($model->published_at)) {
                $model->published_at = now();
            }

            if ($model->status !== PublishStatus::Published->value) {
                $model->published_at = null;
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published->value);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderBy('id');
    }

    public function isPublished(): bool
    {
        return $this->status === PublishStatus::Published->value;
    }
}
