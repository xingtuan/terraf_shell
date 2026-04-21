<?php

namespace App\Models\Concerns;

use App\Support\StorageUrl;

trait HasOptionalMediaUrl
{
    protected static function bootHasOptionalMediaUrl(): void
    {
        static::saving(function ($model): void {
            if (blank($model->media_path)) {
                $model->media_url = null;

                return;
            }

            $model->media_url = StorageUrl::resolve($model->media_path);
        });
    }
}
