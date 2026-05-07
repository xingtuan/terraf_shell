<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();
            $table->integer('change_quantity');
            $table->integer('quantity_before')->nullable();
            $table->integer('quantity_after')->nullable();
            $table->string('reason')->index();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_variant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
