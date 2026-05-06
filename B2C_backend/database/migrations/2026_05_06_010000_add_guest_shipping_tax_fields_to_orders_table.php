<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->string('guest_email')->nullable()->after('user_id')->index();
            $table->string('guest_order_token', 128)->nullable()->after('guest_email')->unique();
            $table->string('shipping_method_code')->nullable()->after('shipping_country');
            $table->string('shipping_method_label')->nullable()->after('shipping_method_code');
            $table->string('shipping_service_code')->nullable()->after('shipping_method_label');
            $table->unsignedSmallInteger('shipping_eta_min_days')->nullable()->after('shipping_service_code');
            $table->unsignedSmallInteger('shipping_eta_max_days')->nullable()->after('shipping_eta_min_days');
            $table->json('shipping_quote_snapshot')->nullable()->after('shipping_eta_max_days');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('shipping_usd');
            $table->decimal('shipping_amount', 10, 2)->nullable()->after('tax_amount');
            $table->decimal('total_amount', 10, 2)->nullable()->after('shipping_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'guest_email',
                'guest_order_token',
                'shipping_method_code',
                'shipping_method_label',
                'shipping_service_code',
                'shipping_eta_min_days',
                'shipping_eta_max_days',
                'shipping_quote_snapshot',
                'tax_amount',
                'shipping_amount',
                'total_amount',
            ]);

            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
