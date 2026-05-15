<?php

namespace App\Exceptions;

use App\Models\ProductVariant;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CartStockLimitException extends ValidationException
{
    /**
     * @var array<string, mixed>
     */
    private array $meta;

    public function __construct(
        ProductVariant $variant,
        int $requestedQuantity,
        int $availableQuantity,
    ) {
        $message = "Only {$availableQuantity} units are available.";
        $validator = Validator::make([], []);
        $validator->errors()->add('quantity', $message);

        parent::__construct($validator);

        $this->meta = [
            'available_quantity' => $availableQuantity,
            'requested_quantity' => $requestedQuantity,
            'product_variant_id' => $variant->id,
            'stock_status' => $variant->stock_status,
            'inventory_policy' => $variant->inventory_policy,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }
}
