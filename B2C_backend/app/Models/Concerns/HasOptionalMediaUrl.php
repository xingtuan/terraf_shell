<?php

namespace App\Models\Concerns;

use App\Support\StorageUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasOptionalMediaUrl
{
    protected static function bootHasOptionalMediaUrl(): void
    {
        static::saving(function ($model): void {
            if (blank($model->media_path)) {
                $model->media_url = null;

                return;
            }

            $model->media_url = StorageUrl::publicResolve($model->media_path);
        });
    }

    protected function mediaUrl(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): ?string => StorageUrl::resolve($attributes['media_path'] ?? null) ?? $value,
        );
    }
}
