<?php

namespace App\Models;

use Database\Factories\PostImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PostImage extends Model
{
    /** @use HasFactory<PostImageFactory> */
    use HasFactory;

    protected $fillable = [
        'post_id',
        'path',
        'url',
        'alt_text',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $image): void {
            if (blank($image->path)) {
                $image->url = '';

                return;
            }

            $image->url = Storage::disk((string) config('community.uploads.disk'))
                ->url($image->path);
        });
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
