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
            'is_guest' => $this->user_id === null,
            'guest_email' => $this->guest_email,
            'guest_order_token' => $this->guest_order_token,
            'status' => $this->status?->value ?? (string) $this->status,
            'payment_status' => $this->payment_status?->value ?? (string) $this->payment_status,
            'item_count' => (int) $this->items->sum('quantity'),
            'subtotal_usd' => number_format((float) $this->subtotal_usd, 2, '.', ''),
            'shipping_usd' => number_format((float) $this->shipping_usd, 2, '.', ''),
            'tax_usd' => number_format((float) $this->tax_amount, 2, '.', ''),
            'tax' => [
                'label' => data_get($this->shipping_quote_snapshot, 'tax.label', 'GST included'),
                'rate' => (float) data_get($this->shipping_quote_snapshot, 'tax.rate', 0.15),
                'amount' => number_format((float) $this->tax_amount, 2, '.', ''),
                'included' => (bool) data_get($this->shipping_quote_snapshot, 'tax.included', true),
            ],
            'total_usd' => number_format((float) $this->total_usd, 2, '.', ''),
            'currency' => $this->currency,
            'shipping_method' => [
                'code' => $this->shipping_method_code,
                'label' => $this->shipping_method_label,
                'service_code' => $this->shipping_service_code,
                'eta_min_days' => $this->shipping_eta_min_days,
                'eta_max_days' => $this->shipping_eta_max_days,
            ],
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
