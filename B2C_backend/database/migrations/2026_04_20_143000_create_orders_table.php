<?php

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', OrderStatus::values())->default(OrderStatus::Pending->value);
            $table->decimal('subtotal_usd', 10, 2);
            $table->decimal('shipping_usd', 10, 2)->default(0);
            $table->decimal('total_usd', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('shipping_name');
            $table->string('shipping_phone')->nullable();
            $table->string('shipping_address_line1');
            $table->string('shipping_address_line2')->nullable();
            $table->string('shipping_city');
            $table->string('shipping_state_province')->nullable();
            $table->string('shipping_postal_code')->nullable();
            $table->string('shipping_country', 2);
            $table->string('customer_note')->nullable();
            $table->string('admin_note')->nullable();
            $table->string('payment_method')->default('manual');
            $table->enum('payment_status', OrderPaymentStatus::values())->default(OrderPaymentStatus::Unpaid->value);
            $table->string('payment_reference')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
