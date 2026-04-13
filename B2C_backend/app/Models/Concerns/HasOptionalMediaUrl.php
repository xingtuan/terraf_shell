<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

trait HasOptionalMediaUrl
{
    protected static function bootHasOptionalMediaUrl(): void
    {
        static::saving(function ($model): void {
            if (blank($model->media_path)) {
                return;
            }

            $model->media_url = Storage::disk((string) config('community.uploads.disk'))
                ->url($model->media_path);
        });
    }
}
