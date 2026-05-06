<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->json('selling_points')->nullable()->after('material_benefits_translations');
            $table->json('shipping_notes')->nullable()->after('selling_points');
            $table->json('return_notes')->nullable()->after('shipping_notes');
            $table->json('product_faqs')->nullable()->after('return_notes');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'selling_points',
                'shipping_notes',
                'return_notes',
                'product_faqs',
            ]);
        });
    }
};
