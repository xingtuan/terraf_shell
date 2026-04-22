<?php

namespace App\Support;

class StorePricing
{
    public const FREE_SHIPPING_THRESHOLD = 200.0;

    public const SHIPPING_RATE = 15.0;

    public const PLACEHOLDER_TAX_RATE = 0.0;

    public static function shippingForSubtotal(float $subtotal): float
    {
        return $subtotal >= self::FREE_SHIPPING_THRESHOLD ? 0.0 : self::SHIPPING_RATE;
    }

    public static function taxForSubtotal(float $subtotal): float
    {
        return round($subtotal * self::PLACEHOLDER_TAX_RATE, 2);
    }

    public static function totalForSubtotal(float $subtotal): float
    {
        return $subtotal + self::shippingForSubtotal($subtotal) + self::taxForSubtotal($subtotal);
    }
}
