<?php

namespace Database\Seeders;

use App\Models\EmailEvent;
use App\Models\EmailTemplate;
use App\Services\Email\EmailCenterDefaults;
use Illuminate\Database\Seeder;

class EmailCenterSeeder extends Seeder
{
    public function run(): void
    {
        foreach (EmailCenterDefaults::events() as $index => $event) {
            EmailEvent::query()->updateOrCreate(
                ['key' => $event['key']],
                [
                    'category' => $event['category'],
                    'name' => $event['name'],
                    'description' => EmailCenterDefaults::description($event['key']),
                    'is_enabled' => (bool) ($event['enabled'] ?? false),
                    'recipient_type' => $event['recipient_type'],
                    'custom_recipients' => [],
                    'template_key' => $event['key'],
                    'throttle_minutes' => $event['throttle'] ?? null,
                    'use_queue' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );

            foreach (EmailCenterDefaults::LOCALES as $locale) {
                EmailTemplate::query()->updateOrCreate(
                    [
                        'key' => $event['key'],
                        'locale' => $locale,
                    ],
                    [
                        'name' => $event['name'].' ('.strtoupper($locale).')',
                        'subject' => EmailCenterDefaults::subject($event['key'], $locale),
                        'preheader' => 'Notification from {{ app.name }}',
                        'html_body' => EmailCenterDefaults::html($event['key']),
                        'text_body' => EmailCenterDefaults::text($event['key']),
                        'available_variables' => EmailCenterDefaults::variables($event['key']),
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
