<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustment extends Model
{
    public const REASON_OPTIONS = [
        'manual_adjustment' => 'Manual adjustment',
        'order_created' => 'Order created',
        'order_cancelled' => 'Order cancelled',
        'return_received' => 'Return received',
        'stock_received' => 'Stock received',
        'damage' => 'Damage',
        'correction' => 'Correction',
    ];

    protected $fillable = [
        'product_variant_id',
        'change_quantity',
        'quantity_before',
        'quantity_after',
        'reason',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'change_quantity' => 'integer',
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
        ];
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
