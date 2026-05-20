<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('home_sections')) {
            return;
        }

        if (! Schema::hasColumn('home_sections', 'page_key')) {
            Schema::table('home_sections', function (Blueprint $table): void {
                $table->string('page_key')->default('home')->after('id')->index();
            });
        }

        DB::table('home_sections')
            ->where(function ($query): void {
                $query->whereNull('page_key')->orWhere('page_key', '');
            })
            ->update(['page_key' => 'home']);

        foreach (Schema::getIndexes('home_sections') as $index) {
            if (
                ($index['unique'] ?? false) !== true ||
                ($index['columns'] ?? []) !== ['key']
            ) {
                continue;
            }

            Schema::table('home_sections', function (Blueprint $table) use ($index): void {
                $table->dropUnique($index['name']);
            });
        }

        if (! Schema::hasIndex('home_sections', ['page_key', 'key'], 'unique')) {
            Schema::table('home_sections', function (Blueprint $table): void {
                $table->unique(['page_key', 'key'], 'home_sections_page_key_key_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('home_sections')) {
            return;
        }

        if (Schema::hasIndex('home_sections', ['page_key', 'key'], 'unique')) {
            Schema::table('home_sections', function (Blueprint $table): void {
                $table->dropUnique('home_sections_page_key_key_unique');
            });
        }
    }
};
