<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_properties', function (Blueprint $table): void {
            $table->id();
            $table->string('key');
            $table->string('locale', 3);
            $table->string('label');
            $table->string('value');
            $table->string('comparison');
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['key', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_properties');
    }
};
