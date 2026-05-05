<?php

namespace App\Services\Email;

class EmailCenterDefaults
{
    public const LOCALES = ['en', 'zh', 'ko'];

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function events(): array
    {
        return [
            ['key' => 'auth.email_verification', 'category' => 'Auth', 'name' => 'Email verification', 'recipient_type' => 'user', 'enabled' => true],
            ['key' => 'auth.email_verification_resent', 'category' => 'Auth', 'name' => 'Email verification resent', 'recipient_type' => 'user', 'enabled' => false],
            ['key' => 'auth.password_reset', 'category' => 'Auth', 'name' => 'Password reset', 'recipient_type' => 'user', 'enabled' => true],
            ['key' => 'auth.password_reset_success', 'category' => 'Auth', 'name' => 'Password reset success', 'recipient_type' => 'user', 'enabled' => false],
            ['key' => 'auth.welcome', 'category' => 'Auth', 'name' => 'Welcome after verification', 'recipient_type' => 'user', 'enabled' => false],

            ['key' => 'order.created', 'category' => 'Store', 'name' => 'Order request received', 'recipient_type' => 'user', 'enabled' => true],
            ['key' => 'order.cancelled', 'category' => 'Store', 'name' => 'Order request cancelled', 'recipient_type' => 'user', 'enabled' => false],
            ['key' => 'order.status_changed', 'category' => 'Store', 'name' => 'Order request status changed', 'recipient_type' => 'user', 'enabled' => false],
            ['key' => 'order.shipped', 'category' => 'Store', 'name' => 'Order shipped', 'recipient_type' => 'user', 'enabled' => false],
            ['key' => 'order.admin_new_order', 'category' => 'Store', 'name' => 'Admin new order request alert', 'recipient_type' => 'admin', 'enabled' => true],

            ['key' => 'inquiry.submitted_user_confirmation', 'category' => 'B2B / Contact', 'name' => 'Inquiry user confirmation', 'recipient_type' => 'user', 'enabled' => true],
            ['key' => 'inquiry.submitted_admin_notification', 'category' => 'B2B / Contact', 'name' => 'Inquiry admin notification', 'recipient_type' => 'admin', 'enabled' => true],
            ['key' => 'b2b_lead.submitted_user_confirmation', 'category' => 'B2B / Contact', 'name' => 'B2B lead user confirmation', 'recipient_type' => 'user', 'enabled' => false],
            ['key' => 'b2b_lead.submitted_admin_notification', 'category' => 'B2B / Contact', 'name' => 'B2B lead admin notification', 'recipient_type' => 'admin', 'enabled' => false],
            ['key' => 'partnership_inquiry.submitted_user_confirmation', 'category' => 'B2B / Contact', 'name' => 'Partnership inquiry confirmation', 'recipient_type' => 'user', 'enabled' => false],
            ['key' => 'sample_request.submitted_user_confirmation', 'category' => 'B2B / Contact', 'name' => 'Sample request confirmation', 'recipient_type' => 'user', 'enabled' => false],
            ['key' => 'lead.assigned_admin_notification', 'category' => 'B2B / Contact', 'name' => 'Lead assigned to admin', 'recipient_type' => 'custom', 'enabled' => false],
            ['key' => 'lead.status_changed_user_update', 'category' => 'B2B / Contact', 'name' => 'Lead status changed user update', 'recipient_type' => 'user', 'enabled' => false],

            ['key' => 'community.comment_created', 'category' => 'Community', 'name' => 'Comment created', 'recipient_type' => 'user', 'enabled' => false, 'throttle' => 30],
            ['key' => 'community.reply_created', 'category' => 'Community', 'name' => 'Reply created', 'recipient_type' => 'user', 'enabled' => false, 'throttle' => 30],
            ['key' => 'community.follow_created', 'category' => 'Community', 'name' => 'Follow created', 'recipient_type' => 'user', 'enabled' => false, 'throttle' => 120],
            ['key' => 'community.post_liked', 'category' => 'Community', 'name' => 'Post liked', 'recipient_type' => 'user', 'enabled' => false, 'throttle' => 240],
            ['key' => 'community.post_favorited', 'category' => 'Community', 'name' => 'Post favorited', 'recipient_type' => 'user', 'enabled' => false, 'throttle' => 240],
            ['key' => 'community.post_approved', 'category' => 'Community', 'name' => 'Post approved', 'recipient_type' => 'user', 'enabled' => true],
            ['key' => 'community.post_rejected', 'category' => 'Community', 'name' => 'Post rejected', 'recipient_type' => 'user', 'enabled' => true],
            ['key' => 'community.post_featured', 'category' => 'Community', 'name' => 'Post featured', 'recipient_type' => 'user', 'enabled' => true],
            ['key' => 'community.system_announcement', 'category' => 'Community', 'name' => 'System announcement email', 'recipient_type' => 'custom', 'enabled' => false],

            ['key' => 'admin.test_email', 'category' => 'Admin/System', 'name' => 'Admin test email', 'recipient_type' => 'custom', 'enabled' => true],
            ['key' => 'admin.manual_announcement', 'category' => 'Admin/System', 'name' => 'Manual announcement', 'recipient_type' => 'custom', 'enabled' => false],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function eventByKey(string $key): array
    {
        return collect(self::events())->firstWhere('key', $key) ?? [];
    }

    public static function subject(string $key, string $locale = 'en'): string
    {
        $subjects = [
            'auth.email_verification' => 'Verify your email for {{ app.name }}',
            'auth.email_verification_resent' => 'Your new {{ app.name }} verification link',
            'auth.password_reset' => 'Reset your {{ app.name }} password',
            'auth.password_reset_success' => 'Your {{ app.name }} password was reset',
            'auth.welcome' => 'Welcome to {{ app.name }}',
            'order.created' => 'Order request {{ order.order_number }} received',
            'order.cancelled' => 'Order request {{ order.order_number }} was cancelled',
            'order.status_changed' => 'Order request {{ order.order_number }} status update',
            'order.shipped' => 'Order {{ order.order_number }} has shipped',
            'order.admin_new_order' => 'New order request {{ order.order_number }}',
            'inquiry.submitted_user_confirmation' => 'We received your inquiry',
            'inquiry.submitted_admin_notification' => 'New inquiry from {{ inquiry.name }}',
            'b2b_lead.submitted_user_confirmation' => 'We received your request',
            'b2b_lead.submitted_admin_notification' => 'New B2B lead {{ lead.reference }}',
            'partnership_inquiry.submitted_user_confirmation' => 'We received your partnership inquiry',
            'sample_request.submitted_user_confirmation' => 'We received your sample request',
            'lead.assigned_admin_notification' => 'Lead {{ lead.reference }} was assigned',
            'lead.status_changed_user_update' => 'Your inquiry status changed',
            'community.comment_created' => 'New comment on {{ post.title }}',
            'community.reply_created' => 'New reply on {{ post.title }}',
            'community.follow_created' => '{{ actor.name }} started following you',
            'community.post_liked' => '{{ actor.name }} liked {{ post.title }}',
            'community.post_favorited' => '{{ actor.name }} favorited {{ post.title }}',
            'community.post_approved' => 'Your post was approved',
            'community.post_rejected' => 'Your post needs updates',
            'community.post_featured' => 'Your post was featured',
            'community.system_announcement' => '{{ announcement.title }}',
            'admin.test_email' => '{{ app.name }} Email Center test',
            'admin.manual_announcement' => '{{ announcement.title }}',
        ];

        $subject = $subjects[$key] ?? '{{ app.name }} notification';

        return match ($locale) {
            'zh' => '[ZH] '.$subject,
            'ko' => '[KO] '.$subject,
            default => $subject,
        };
    }

    public static function html(string $key): string
    {
        return match (true) {
            str_starts_with($key, 'auth.email_verification') => '<p>Hello {{ user.name }},</p><p>Please verify {{ user.email }} for {{ app.name }}.</p><p><a href="{{ verification_url }}">Verify email</a></p>',
            $key === 'auth.password_reset' => '<p>Hello {{ user.name }},</p><p>Use the link below to reset your password. This link expires in {{ expires_minutes }} minutes.</p><p><a href="{{ reset_url }}">Reset password</a></p>',
            $key === 'auth.password_reset_success' => '<p>Hello {{ user.name }},</p><p>Your {{ app.name }} password was reset successfully.</p>',
            $key === 'auth.welcome' => '<p>Hello {{ user.name }},</p><p>Welcome to {{ app.name }}.</p>',
            str_contains($key, 'admin_new_order') => '<p>A new order request was submitted.</p><p>Order request: {{ order.order_number }}</p><p>Customer: {{ user.name }} {{ user.email }}</p><p>Total estimate: {{ order.total }} {{ order.currency }}</p><p>Payment will be handled manually.</p>',
            $key === 'order.created' => '<p>Hello {{ user.name }},</p><p>Your order request {{ order.order_number }} has been submitted. Our team will review stock, shipping, tax, and payment details, then contact you by email.</p><p>Online payment is not available yet.</p><p>Status: {{ order.status }}</p><p>Total estimate: {{ order.total }} {{ order.currency }}</p><p>{{ order.items }}</p><p><a href="{{ order_url }}">View order request</a></p><p>Shipping: {{ shipping.address }}</p>',
            $key === 'order.cancelled' => '<p>Hello {{ user.name }},</p><p>Order request {{ order.order_number }} was cancelled.</p><p><a href="{{ order_url }}">View order request</a></p>',
            str_starts_with($key, 'order.') => '<p>Hello {{ user.name }},</p><p>Order request {{ order.order_number }} is currently {{ order.status }}.</p><p>Total estimate: {{ order.total }} {{ order.currency }}</p><p>{{ order.items }}</p><p><a href="{{ order_url }}">View order request</a></p><p>Shipping: {{ shipping.address }}</p>',
            $key === 'inquiry.submitted_user_confirmation'
                || $key === 'b2b_lead.submitted_user_confirmation'
                || $key === 'partnership_inquiry.submitted_user_confirmation'
                || $key === 'sample_request.submitted_user_confirmation' => '<p>Hello {{ inquiry.name }},</p><p>We have received your B2B enquiry. Our team will review your application and contact you with the next steps.</p><p>Reference: {{ lead.reference }}</p><p>Company: {{ inquiry.company }}</p><p>Interest type: {{ inquiry.interest_type }}</p><p>Application: {{ inquiry.application }}</p>',
            $key === 'inquiry.submitted_admin_notification'
                || $key === 'b2b_lead.submitted_admin_notification' => '<p>A new B2B enquiry has been submitted.</p><p>Reference: {{ lead.reference }}</p><p>Company: {{ inquiry.company }}</p><p>Contact person: {{ inquiry.name }} ({{ inquiry.email }})</p><p>Interest type: {{ inquiry.interest_type }}</p><p>Application: {{ inquiry.application }}</p><p>Estimated quantity: {{ inquiry.estimated_quantity }}</p><p>Message: {{ inquiry.message }}</p>',
            str_contains($key, 'inquiry') || str_contains($key, 'lead') || str_contains($key, 'sample_request') => '<p>Hello {{ inquiry.name }},</p><p>Reference: {{ lead.reference }}</p><p>Company: {{ inquiry.company }}</p><p>Message: {{ inquiry.message }}</p><p>Status: {{ lead.status }}</p>',
            $key === 'community.post_rejected' => '<p>Hello {{ user.name }},</p><p>Your post "{{ post.title }}" needs updates.</p><p>Reason: {{ reason }}</p><p><a href="{{ edit_url }}">Edit post</a></p>',
            str_starts_with($key, 'community.') => '<p>Hello {{ user.name }},</p><p>{{ actor.name }} sent an update about "{{ post.title }}".</p><p><a href="{{ post_url }}">View post</a></p>',
            str_contains($key, 'announcement') => '<p>{{ announcement.body }}</p><p><a href="{{ action_url }}">Open</a></p>',
            default => '<p>Hello {{ user.name }},</p><p>This is a notification from {{ app.name }}.</p><p><a href="{{ action_url }}">Open</a></p>',
        };
    }

    public static function text(string $key): string
    {
        return strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], "\n", self::html($key)));
    }

    /**
     * @return array<int, string>
     */
    public static function variables(string $key): array
    {
        $common = ['app.name', 'app.url', 'user.name', 'user.email', 'action_url'];

        return array_values(array_unique(array_merge($common, match (true) {
            str_starts_with($key, 'auth.email_verification') => ['verification_url'],
            $key === 'auth.password_reset' => ['reset_url', 'expires_minutes'],
            str_starts_with($key, 'order.') => ['order.order_number', 'order.status', 'order.total', 'order.currency', 'order.items', 'order_url', 'shipping.address', 'customer_note'],
            str_contains($key, 'inquiry') || str_contains($key, 'lead') || str_contains($key, 'sample_request') => ['inquiry.name', 'inquiry.email', 'inquiry.company', 'inquiry.message', 'inquiry.type', 'inquiry.interest_type', 'inquiry.application', 'inquiry.estimated_quantity', 'inquiry.timeline', 'lead.reference', 'lead.status', 'lead.interest_type', 'lead.application_type', 'lead.expected_use_case', 'lead.estimated_quantity', 'lead.timeline', 'assignee.name'],
            str_starts_with($key, 'community.') => ['post.title', 'post.slug', 'post_url', 'moderator.name', 'actor.name', 'reason', 'edit_url', 'announcement.title', 'announcement.body'],
            str_contains($key, 'announcement') => ['announcement.title', 'announcement.body'],
            default => [],
        })));
    }

    public static function description(string $key): string
    {
        return 'Controls email delivery for '.$key.'.';
    }
}
