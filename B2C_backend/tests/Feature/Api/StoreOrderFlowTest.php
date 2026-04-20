<?php

namespace Tests\Feature\Api;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cart_can_be_merged_into_user_cart_and_checked_out(): void
    {
        $product = Product::factory()->published()->create([
            'price_usd' => 48.00,
            'is_active' => true,
            'in_stock' => true,
        ]);

        $guestCartResponse = $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('data.item_count', 0)
            ->assertCookie('shellfin_cart_session');

        $sessionKey = Cart::query()
            ->whereNull('user_id')
            ->value('session_key');

        $this->assertNotNull($sessionKey);

        $this->withCookie('shellfin_cart_session', $sessionKey)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('data.item_count', 2)
            ->assertJsonPath('data.subtotal_usd', '96.00');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/cart/merge', [
            'session_key' => $sessionKey,
        ])
            ->assertOk();

        $cart = Cart::query()->where('user_id', $user->id)->with('items')->first();

        $this->assertNotNull($cart);
        $this->assertSame(2, $cart->itemCount());
        $this->assertDatabaseCount('cart_items', 1);

        $orderResponse = $this->postJson('/api/orders', [
            'shipping_name' => 'Shellfin Buyer',
            'shipping_phone' => '+64-21-000-000',
            'shipping_address_line1' => '123 Ocean Road',
            'shipping_city' => 'Auckland',
            'shipping_state_province' => 'Auckland',
            'shipping_postal_code' => '1010',
            'shipping_country' => 'NZ',
            'customer_note' => 'Please confirm the finish before shipping.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_status', 'unpaid')
            ->assertJsonPath('data.subtotal_usd', '96.00')
            ->assertJsonPath('data.shipping_usd', '15.00')
            ->assertJsonPath('data.total_usd', '111.00')
            ->assertJsonPath('data.items.0.product_id', $product->id)
            ->assertJsonPath('data.items.0.quantity', 2);

        $orderNumber = $orderResponse->json('data.order_number');

        $this->assertMatchesRegularExpression('/^SHF-\d{6}$/', $orderNumber);
        $this->assertDatabaseHas('orders', [
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'shipping_country' => 'NZ',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price_usd' => '48.00',
            'subtotal_usd' => '96.00',
        ]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_user_can_manage_addresses_and_cancel_pending_order(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $addressResponse = $this->postJson('/api/addresses', [
            'label' => 'Home',
            'recipient_name' => 'Shellfin Buyer',
            'phone' => '+64-21-000-000',
            'address_line1' => '123 Ocean Road',
            'city' => 'Auckland',
            'country' => 'NZ',
            'is_default' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.label', 'Home')
            ->assertJsonPath('data.is_default', true);

        $addressId = $addressResponse->json('data.id');

        $secondAddress = Address::query()->create([
            'user_id' => $user->id,
            'label' => 'Office',
            'recipient_name' => 'Shellfin Buyer',
            'address_line1' => '10 Harbour Street',
            'city' => 'Auckland',
            'country' => 'NZ',
            'is_default' => false,
        ]);

        $this->postJson("/api/addresses/{$secondAddress->id}/default")
            ->assertOk()
            ->assertJsonPath('data.id', $secondAddress->id)
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('addresses', [
            'id' => $addressId,
            'is_default' => false,
        ]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => 'SHF-000001',
            'status' => 'pending',
            'subtotal_usd' => 48.00,
            'shipping_usd' => 15.00,
            'total_usd' => 63.00,
            'currency' => 'USD',
            'shipping_name' => 'Shellfin Buyer',
            'shipping_address_line1' => '123 Ocean Road',
            'shipping_city' => 'Auckland',
            'shipping_country' => 'NZ',
            'payment_method' => 'manual',
            'payment_status' => 'unpaid',
        ]);

        $this->deleteJson("/api/orders/{$order->order_number}")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }
}
