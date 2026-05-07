<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'product_sku',
        'product_title',
        'variant_title',
        'variant_sku',
        'option_values',
        'quantity',
        'unit_price_usd',
        'unit_price_amount',
        'currency',
        'subtotal_usd',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'option_values' => 'array',
            'unit_price_usd' => 'decimal:2',
            'unit_price_amount' => 'decimal:2',
            'subtotal_usd' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
