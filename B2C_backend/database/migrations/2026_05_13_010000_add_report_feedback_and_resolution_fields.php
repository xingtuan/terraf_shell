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
        Schema::table('reports', function (Blueprint $table) {
            $table->text('public_note')->nullable()->after('moderator_note');
            $table->timestamp('resolved_at')->nullable()->after('reviewed_at');
            $table->timestamp('dismissed_at')->nullable()->after('resolved_at');
            $table->timestamp('reporter_notified_at')->nullable()->after('dismissed_at');
            $table->string('resolution_action')->nullable()->after('reporter_notified_at');
            $table->index(['status', 'resolved_at']);
            $table->index(['status', 'dismissed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['status', 'resolved_at']);
            $table->dropIndex(['status', 'dismissed_at']);
            $table->dropColumn([
                'public_note',
                'resolved_at',
                'dismissed_at',
                'reporter_notified_at',
                'resolution_action',
            ]);
        });
    }
};
