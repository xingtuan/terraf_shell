<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Concerns\HasOptionalMediaUrl;
use Database\Factories\ProductImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    /** @use HasFactory<ProductImageFactory> */
    use HasFactory, HasLocalizedAttributes, HasOptionalMediaUrl;

    protected array $localizedAttributes = [
        'alt_text',
        'caption',
    ];

    protected $fillable = [
        'product_id',
        'alt_text',
        'alt_text_translations',
        'caption',
        'caption_translations',
        'media_path',
        'media_url',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'alt_text_translations' => 'array',
            'caption_translations' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
