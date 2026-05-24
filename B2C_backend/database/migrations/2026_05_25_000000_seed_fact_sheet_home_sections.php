<?php

use App\Support\DefaultPageSections;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('home_sections') || ! Schema::hasColumn('home_sections', 'page_key')) {
            return;
        }

        DefaultPageSections::seedFactSheetSections();
    }

    public function down(): void
    {
        // One-way content backfill.
    }
};
