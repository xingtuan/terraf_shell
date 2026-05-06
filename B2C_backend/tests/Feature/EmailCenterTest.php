<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\Cart;
use App\Models\EmailEvent;
use App\Models\EmailLog;
use App\Models\EmailSetting;
use App\Models\EmailTemplate;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Services\AdminModerationService;
use App\Services\Email\EmailDispatchService;
use App\Services\Email\EmailTemplateRenderer;
use App\Services\Email\MailSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_email_settings_and_secrets_are_encrypted_and_masked(): void
    {
        $admin = User::factory()->admin()->create();

        $settings = app(MailSettingsService::class)->save([
            'is_enabled' => true,
            'mailer' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'mailer',
            'password' => 'super-secret',
            'api_key' => 'api-secret',
            'from_address' => 'hello@example.com',
            'from_name' => 'OXP',
            'admin_recipients' => ['ops@example.com'],
            'use_queue' => true,
        ], $admin);

        $raw = DB::table('email_settings')->where('id', $settings->id)->value('password');

        $this->assertNotSame('super-secret', $raw);
        $this->assertSame('super-secret', $settings->fresh()->password);
        $this->assertSame('********', $settings->fresh()->maskedPassword());
        $this->assertSame('********', $settings->fresh()->maskedApiKey());
    }

    public function test_only_admin_can_access_email_center(): void
    {
        $admin = User::factory()->admin()->create();
        $moderator = User::factory()->moderator()->create();

        $this->actingAs($admin)->get('/admin/email-settings')->assertOk();
        $this->actingAs($moderator)->get('/admin/email-settings')->assertForbidden();
    }

    public function test_test_email_creates_email_log(): void
    {
        Mail::fake();
        $this->enableEmailCenter(['admin.test_email']);

        $log = app(EmailDispatchService::class)->sendTest('ops@example.com');

        $this->assertDatabaseHas('email_logs', [
            'id' => $log->id,
            'event_key' => 'admin.test_email',
            'status' => EmailLog::STATUS_SENT,
        ]);
    }

    public function test_disabled_global_and_disabled_event_skip_send(): void
    {
        Mail::fake();
        $this->enableEmailCenter(['auth.email_verification'], enabled: false);

        $user = User::factory()->create();
        $globalLog = app(EmailDispatchService::class)->sendEvent('auth.email_verification', ['user' => $user]);

        $this->assertSame(EmailLog::STATUS_SKIPPED, $globalLog->status);
        $this->assertSame('global_disabled', $globalLog->skip_reason);

        EmailSetting::query()->update(['is_enabled' => true]);
        EmailEvent::query()->where('key', 'auth.email_verification')->update(['is_enabled' => false]);

        $eventLog = app(EmailDispatchService::class)->sendEvent('auth.email_verification', ['user' => $user]);

        $this->assertSame(EmailLog::STATUS_SKIPPED, $eventLog->status);
        $this->assertSame('event_disabled', $eventLog->skip_reason);
    }

    public function test_missing_locale_falls_back_to_english_template(): void
    {
        Mail::fake();
        $this->enableEmailCenter(['auth.email_verification'], locales: ['en']);

        $user = User::factory()->create();
        $log = app(EmailDispatchService::class)->sendEvent('auth.email_verification', [
            'user' => $user,
            'verification_url' => 'https://example.com/verify',
        ], [
            'locale' => 'ko',
            'sync' => true,
        ]);

        $this->assertSame('en', $log->fresh()->locale);
    }

    public function test_template_renderer_replaces_variables_and_does_not_execute_code(): void
    {
        $rendered = app(EmailTemplateRenderer::class)->render([
            'subject' => 'Hello {{ user.name }}',
            'html_body' => '<p>{{ user.name }}</p><?php echo "bad"; ?><script>alert(1)</script>@if(true) Blade @endif',
            'text_body' => 'Email: {{ user.email }}',
        ], [
            'user' => [
                'name' => '<Admin>',
                'email' => 'admin@example.com',
            ],
        ]);

        $this->assertSame('Hello <Admin>', $rendered['subject']);
        $this->assertStringContainsString('&lt;Admin&gt;', $rendered['html']);
        $this->assertStringNotContainsString('bad', $rendered['html']);
        $this->assertStringNotContainsString('<script', $rendered['html']);
        $this->assertStringNotContainsString('@if', $rendered['html']);
        $this->assertSame('Email: admin@example.com', $rendered['text']);
    }

    public function test_registration_and_forgot_password_dispatch_email_center_events_when_enabled(): void
    {
        Mail::fake();
        $this->enableEmailCenter(['auth.email_verification', 'auth.password_reset']);

        $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated();

        $this->assertDatabaseHas('email_logs', [
            'event_key' => 'auth.email_verification',
            'status' => EmailLog::STATUS_SENT,
        ]);

        $this->postJson('/api/auth/forgot-password', [
            'email' => 'jane@example.com',
        ])->assertOk();

        $this->assertDatabaseHas('email_logs', [
            'event_key' => 'auth.password_reset',
            'status' => EmailLog::STATUS_SENT,
        ]);
    }

    public function test_order_creation_dispatches_customer_and_admin_emails(): void
    {
        Mail::fake();
        $this->enableEmailCenter(['order.created', 'order.admin_new_order']);

        $user = User::factory()->create();
        $product = Product::factory()->published()->create([
            'price_usd' => 48.00,
            'is_active' => true,
            'in_stock' => true,
            'stock_status' => 'in_stock',
        ]);
        $cart = Cart::query()->create(['user_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_usd' => 48.00,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/orders', [
            'shipping_method_code' => 'standard',
            'shipping_name' => 'OXP Buyer',
            'shipping_phone' => '+64 21 000 000',
            'shipping_address_line1' => '123 Ocean Road',
            'shipping_city' => 'Auckland',
            'shipping_postal_code' => '1010',
            'shipping_country' => 'NZ',
        ])->assertCreated();

        $this->assertDatabaseHas('email_logs', ['event_key' => 'order.created']);
        $this->assertDatabaseHas('email_logs', ['event_key' => 'order.admin_new_order']);
    }

    public function test_inquiry_submission_dispatches_user_and_admin_emails(): void
    {
        Mail::fake();
        $this->enableEmailCenter([
            'inquiry.submitted_user_confirmation',
            'inquiry.submitted_admin_notification',
        ]);

        $this->postJson('/api/inquiries', [
            'name' => 'Jane Doe',
            'company_name' => 'OXP Studio',
            'email' => 'jane@example.com',
            'inquiry_type' => 'Sample Request',
            'message' => 'We need pellets for a pilot project.',
        ])->assertCreated();

        $this->assertDatabaseHas('email_logs', ['event_key' => 'inquiry.submitted_user_confirmation']);
        $this->assertDatabaseHas('email_logs', ['event_key' => 'inquiry.submitted_admin_notification']);
    }

    public function test_community_post_approval_dispatches_email_when_enabled(): void
    {
        Mail::fake();
        $this->enableEmailCenter(['community.post_approved']);

        $admin = User::factory()->admin()->create();
        $post = Post::factory()->pending()->create();

        app(AdminModerationService::class)->updatePostStatus(
            $post,
            ContentStatus::Approved->value,
            $admin,
        );

        $this->assertDatabaseHas('email_logs', [
            'event_key' => 'community.post_approved',
        ]);
    }

    public function test_failed_send_updates_email_log(): void
    {
        $this->enableEmailCenter(['admin.test_email']);

        $log = EmailLog::query()->create([
            'event_key' => 'admin.test_email',
            'template_key' => 'admin.test_email',
            'locale' => 'en',
            'mailer' => 'array',
            'to' => [['email' => 'ops@example.com', 'name' => null]],
            'subject' => 'Broken',
            'status' => EmailLog::STATUS_QUEUED,
            'payload' => [],
            'queued_at' => now(),
        ]);

        app(EmailDispatchService::class)->sendLog($log);

        $this->assertDatabaseHas('email_logs', [
            'id' => $log->id,
            'status' => EmailLog::STATUS_FAILED,
        ]);
    }

    /**
     * @param  array<int, string>  $events
     * @param  array<int, string>  $locales
     */
    private function enableEmailCenter(array $events, bool $enabled = true, array $locales = ['en', 'zh', 'ko']): void
    {
        EmailSetting::query()->create([
            'is_enabled' => $enabled,
            'mailer' => 'array',
            'from_address' => 'hello@example.com',
            'from_name' => 'OXP',
            'admin_recipients' => [['email' => 'ops@example.com', 'name' => 'Ops']],
            'use_queue' => false,
        ]);

        foreach ($events as $eventKey) {
            EmailEvent::query()->create([
                'key' => $eventKey,
                'category' => str_starts_with($eventKey, 'order.') ? 'Store' : 'Auth',
                'name' => $eventKey,
                'is_enabled' => true,
                'recipient_type' => str_contains($eventKey, 'admin') ? 'admin' : 'user',
                'template_key' => $eventKey,
                'use_queue' => false,
            ]);

            foreach ($locales as $locale) {
                EmailTemplate::query()->create([
                    'key' => $eventKey,
                    'locale' => $locale,
                    'name' => "{$eventKey} {$locale}",
                    'subject' => 'Subject {{ user.name }}',
                    'html_body' => '<p>Hello {{ user.name }}</p><p><a href="{{ action_url }}">Open</a></p>',
                    'text_body' => 'Hello {{ user.email }}',
                    'available_variables' => ['user.name', 'user.email', 'action_url'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
