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
        $columns = Schema::getColumnListing('reports');

        Schema::table('reports', function (Blueprint $table) use ($columns): void {
            if (! in_array('public_note', $columns, true)) {
                $table->text('public_note')->nullable()->after(in_array('moderator_note', $columns, true) ? 'moderator_note' : 'status');
            }

            if (! in_array('resolved_at', $columns, true)) {
                $table->timestamp('resolved_at')->nullable()->after('reviewed_at');
            }

            if (! in_array('dismissed_at', $columns, true)) {
                $table->timestamp('dismissed_at')->nullable()->after(in_array('resolved_at', $columns, true) ? 'resolved_at' : 'reviewed_at');
            }

            if (! in_array('completed_at', $columns, true)) {
                $table->timestamp('completed_at')->nullable()->after(in_array('dismissed_at', $columns, true) ? 'dismissed_at' : 'reviewed_at');
            }

            if (! in_array('resolution_action', $columns, true)) {
                $table->string('resolution_action')->nullable()->after(in_array('completed_at', $columns, true) ? 'completed_at' : 'reviewed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('reports', 'completed_at')) {
            Schema::table('reports', function (Blueprint $table): void {
                $table->dropColumn('completed_at');
            });
        }
    }
};
