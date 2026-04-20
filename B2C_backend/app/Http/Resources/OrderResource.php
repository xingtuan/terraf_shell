<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->loadMissing(['items.product']);

        return [
            'order_number' => $this->order_number,
            'status' => $this->status?->value ?? (string) $this->status,
            'payment_status' => $this->payment_status?->value ?? (string) $this->payment_status,
            'subtotal_usd' => number_format((float) $this->subtotal_usd, 2, '.', ''),
            'shipping_usd' => number_format((float) $this->shipping_usd, 2, '.', ''),
            'total_usd' => number_format((float) $this->total_usd, 2, '.', ''),
            'currency' => $this->currency,
            'shipping_address' => [
                'name' => $this->shipping_name,
                'phone' => $this->shipping_phone,
                'address_line1' => $this->shipping_address_line1,
                'address_line2' => $this->shipping_address_line2,
                'city' => $this->shipping_city,
                'state_province' => $this->shipping_state_province,
                'postal_code' => $this->shipping_postal_code,
                'country' => $this->shipping_country,
            ],
            'customer_note' => $this->customer_note,
            'items' => OrderItemResource::collection($this->items),
            'created_at' => $this->created_at?->toISOString(),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'shipped_at' => $this->shipped_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
        ];
    }
}
