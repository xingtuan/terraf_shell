<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('mailer', 40)->default('log');
            $table->string('host')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('encryption', 20)->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->text('api_key')->nullable();
            $table->string('domain')->nullable();
            $table->string('region')->nullable();
            $table->string('from_address');
            $table->string('from_name');
            $table->string('reply_to_address')->nullable();
            $table->string('reply_to_name')->nullable();
            $table->json('admin_recipients')->nullable();
            $table->unsignedInteger('timeout')->nullable();
            $table->boolean('use_queue')->default(true);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('email_events', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('category', 80)->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->string('recipient_type', 20)->default('user');
            $table->json('custom_recipients')->nullable();
            $table->string('template_key');
            $table->unsignedInteger('throttle_minutes')->nullable();
            $table->boolean('use_queue')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['category', 'sort_order']);
            $table->index('template_key');
        });

        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key');
            $table->string('locale', 8)->default('en');
            $table->string('name');
            $table->string('subject');
            $table->string('preheader')->nullable();
            $table->longText('html_body');
            $table->longText('text_body')->nullable();
            $table->json('available_variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['key', 'locale']);
            $table->index(['key', 'is_active']);
        });

        Schema::create('email_template_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('email_template_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('locale', 8);
            $table->unsignedInteger('version');
            $table->string('subject');
            $table->string('preheader')->nullable();
            $table->longText('html_body');
            $table->longText('text_body')->nullable();
            $table->json('available_variables')->nullable();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['key', 'locale', 'version']);
        });

        Schema::create('email_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event_key')->nullable()->index();
            $table->string('template_key')->nullable()->index();
            $table->string('locale', 8)->nullable();
            $table->string('mailer')->nullable();
            $table->json('to');
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('subject')->nullable();
            $table->string('status', 20)->default('queued')->index();
            $table->string('skip_reason')->nullable();
            $table->text('error_message')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->nullableMorphs('related');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['event_key', 'idempotency_key']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('email_template_versions');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('email_events');
        Schema::dropIfExists('email_settings');
    }
};
