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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('title')->nullable()->after('type');
            $table->text('body')->nullable()->after('title');
            $table->string('action_url')->nullable()->after('body');
            $table->index(
                ['recipient_user_id', 'type', 'is_read', 'created_at'],
                'notifications_recipient_type_read_created_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_recipient_type_read_created_index');
            $table->dropColumn(['title', 'body', 'action_url']);
        });
    }
};
