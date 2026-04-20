<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'session_key',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(
            Product::class,
            CartItem::class,
            'cart_id',
            'id',
            'id',
            'product_id',
        );
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSession(Builder $query, string $sessionKey): Builder
    {
        return $query->where('session_key', $sessionKey);
    }

    public function total(): string
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        $total = $items->sum(
            fn (CartItem $item): float => (float) $item->unit_price_usd * $item->quantity
        );

        return number_format($total, 2, '.', '');
    }

    public function itemCount(): int
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get(['quantity']);

        return (int) $items->sum('quantity');
    }
}
