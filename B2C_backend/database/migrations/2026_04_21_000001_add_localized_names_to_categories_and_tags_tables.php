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
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_ko')->nullable()->after('name');
            $table->string('name_zh')->nullable()->after('name_ko');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->string('name_ko')->nullable()->after('name');
            $table->string('name_zh')->nullable()->after('name_ko');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['name_ko', 'name_zh']);
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn(['name_ko', 'name_zh']);
        });
    }
};
