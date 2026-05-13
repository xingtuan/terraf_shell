<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory, HasLocalizedAttributes;

    protected array $localizedAttributes = [
        'name',
    ];

    protected $fillable = [
        'name',
        'name_translations',
        'slug',
    ];

    protected function casts(): array
    {
        return [
            'name_translations' => 'array',
        ];
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tags')->withTimestamps();
    }
}
