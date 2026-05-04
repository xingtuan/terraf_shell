<?php

use App\Models\EmailEvent;
use App\Models\EmailTemplate;
use App\Services\Email\EmailDispatchService;
use App\Services\Email\EmailTemplateRenderer;
use Database\Seeders\EmailCenterSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('email:center:seed', function (): int {
    app(EmailCenterSeeder::class)->run();
    $this->info('Email Center defaults seeded.');

    return Command::SUCCESS;
})->purpose('Seed Email Center events and templates');

Artisan::command('email:center:test {email}', function (string $email): int {
    $log = app(EmailDispatchService::class)->sendTest($email);

    $this->info("Test email {$log->status}. Log #{$log->id}.");

    if ($log->error_message) {
        $this->error($log->error_message);
    }

    return $log->status === 'failed' ? Command::FAILURE : Command::SUCCESS;
})->purpose('Send an Email Center test email');

Artisan::command('email:center:preview {eventKey} {--locale=en}', function (string $eventKey): int {
    $renderer = app(EmailTemplateRenderer::class);
    $event = EmailEvent::query()->where('key', $eventKey)->first();

    if (! $event instanceof EmailEvent) {
        $this->error("Email event [{$eventKey}] was not found.");

        return Command::FAILURE;
    }

    $locale = (string) $this->option('locale');
    $template = EmailTemplate::query()
        ->where('key', $event->template_key)
        ->where('locale', $locale)
        ->where('is_active', true)
        ->first()
        ?: EmailTemplate::query()
            ->where('key', $event->template_key)
            ->where('locale', 'en')
            ->where('is_active', true)
            ->first();

    if (! $template instanceof EmailTemplate) {
        $this->error("Template [{$event->template_key}] was not found.");

        return Command::FAILURE;
    }

    $rendered = $renderer->render([
        'subject' => $template->subject,
        'html_body' => $template->html_body,
        'text_body' => $template->text_body,
    ], $renderer->samplePayload($eventKey));

    $this->line('Subject: '.$rendered['subject']);
    $this->line('');
    $this->line($rendered['text'] ?: strip_tags($rendered['html']));

    return Command::SUCCESS;
})->purpose('Preview a rendered Email Center template');
