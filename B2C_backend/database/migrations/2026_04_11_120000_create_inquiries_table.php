<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('company_name', 150);
            $table->string('email', 255);
            $table->string('phone', 40)->nullable();
            $table->string('country', 120)->nullable();
            $table->string('inquiry_type', 150);
            $table->text('message');
            $table->string('source_page', 120)->nullable();
            $table->string('status', 40)->default('new')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
