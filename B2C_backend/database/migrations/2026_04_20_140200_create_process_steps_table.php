<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_steps', function (Blueprint $table): void {
            $table->id();
            $table->integer('step_number');
            $table->string('locale', 3);
            $table->string('title');
            $table->text('body');
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['step_number', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_steps');
    }
};
