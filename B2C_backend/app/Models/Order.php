<?php

namespace App\Models;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'guest_email',
        'guest_order_token',
        'status',
        'subtotal_usd',
        'shipping_usd',
        'tax_amount',
        'shipping_amount',
        'total_amount',
        'total_usd',
        'currency',
        'shipping_name',
        'shipping_phone',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_state_province',
        'shipping_postal_code',
        'shipping_country',
        'shipping_method_code',
        'shipping_method_label',
        'shipping_service_code',
        'shipping_eta_min_days',
        'shipping_eta_max_days',
        'shipping_quote_snapshot',
        'customer_note',
        'admin_note',
        'payment_method',
        'payment_status',
        'payment_reference',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => OrderPaymentStatus::class,
            'subtotal_usd' => 'decimal:2',
            'shipping_usd' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'total_usd' => 'decimal:2',
            'shipping_quote_snapshot' => 'array',
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            if (filled($order->order_number)) {
                return;
            }

            $latest = static::max('id') ?? 0;
            $order->order_number = 'OXP-'.str_pad((string) ($latest + 1), 6, '0', STR_PAD_LEFT);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getFormattedTotalAttribute(): string
    {
        return '$'.number_format((float) $this->total_usd, 2).' USD';
    }
}
