<?php

use App\Enums\AccountStatus;
use App\Models\EmailEvent;
use App\Models\EmailTemplate;
use App\Services\Email\EmailDispatchService;
use App\Services\Email\EmailTemplateRenderer;
use App\Support\LocalStorageReadiness;
use App\Support\MaterialCmsToHomeSectionsMigrator;
use Database\Seeders\EmailCenterSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

Artisan::command('cms:migrate-materials-to-page-sections {--force}', function (): int {
    $summary = app(MaterialCmsToHomeSectionsMigrator::class)->migrate((bool) $this->option('force'));

    $this->info("Material CMS migration complete. Created: {$summary['created']}; updated: {$summary['updated']}; skipped: ".count($summary['skipped']).'.');

    foreach ($summary['skipped'] as $section) {
        $this->line("Skipped existing admin-edited section: {$section}");
    }

    return Command::SUCCESS;
})->purpose('Copy legacy Material CMS content into material Page Sections');

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

Artisan::command('deploy:verify', function (): int {
    $isProduction = app()->environment('production');
    $storageReadiness = LocalStorageReadiness::check((string) config('community.uploads.disk', config('filesystems.default')));
    $databaseReachable = true;

    try {
        DB::select('select 1');
    } catch (Throwable) {
        $databaseReachable = false;
    }

    $checks = [
        ['PHP intl extension', extension_loaded('intl'), 'Install ext-intl in the production PHP runtime.'],
        ['APP_ENV=production', $isProduction, 'Set APP_ENV=production in the production .env.'],
        ['APP_DEBUG=false', ! (bool) config('app.debug'), 'Set APP_DEBUG=false before public launch.'],
        ['Database connection', $databaseReachable, 'Check DB credentials and network access.'],
        ['Local upload directory writable', $storageReadiness['writable'], 'Make storage/app/public writable by the PHP user.'],
        ['Public storage link', ! $storageReadiness['public'] || $storageReadiness['link_exists'], 'Run php artisan storage:link when local public storage is selected.'],
        ['Config cached', app()->configurationIsCached(), 'Run php artisan config:cache after setting production .env.'],
        ['Routes cached', app()->routesAreCached(), 'Run php artisan route:cache after deployment.'],
        ['Queue is async in production', ! $isProduction || config('queue.default') !== 'sync', 'Use a queue worker in production instead of QUEUE_CONNECTION=sync.'],
        ['Mail is not log in production', ! $isProduction || config('mail.default') !== 'log', 'Configure SMTP or the production mail provider.'],
    ];

    $failed = false;

    $this->table(['Check', 'Status', 'Remediation'], array_map(
        function (array $check) use (&$failed): array {
            [$label, $ok, $remediation] = $check;

            if (! $ok) {
                $failed = true;
            }

            return [$label, $ok ? 'ok' : 'fail', $ok ? '-' : $remediation];
        },
        $checks
    ));

    if ($failed) {
        $this->error('Deployment verification found items that need attention.');

        return Command::FAILURE;
    }

    $this->info('Deployment verification passed.');

    return Command::SUCCESS;
})->purpose('Verify production deployment readiness checks');

Artisan::command('admin:check-translations', function (): int {
    $locales = ['en', 'ko', 'zh'];
    $keysByLocale = [];

    foreach ($locales as $locale) {
        $path = lang_path("{$locale}/admin.php");

        if (! file_exists($path)) {
            $this->error("Missing admin translation file: {$path}");

            return Command::FAILURE;
        }

        $translations = require $path;
        $keysByLocale[$locale] = array_keys(Arr::dot($translations));
        sort($keysByLocale[$locale]);
    }

    $referenceKeys = $keysByLocale['en'];
    $failed = false;

    foreach ($locales as $locale) {
        $missing = array_values(array_diff($referenceKeys, $keysByLocale[$locale]));
        $extra = array_values(array_diff($keysByLocale[$locale], $referenceKeys));

        if ($missing !== [] || $extra !== []) {
            $failed = true;
            $this->error("admin.php key mismatch for [{$locale}].");

            foreach ($missing as $key) {
                $this->line("  missing: {$key}");
            }

            foreach ($extra as $key) {
                $this->line("  extra: {$key}");
            }
        }
    }

    if ($failed) {
        return Command::FAILURE;
    }

    $this->info('Admin translation keys match for en, ko, and zh.');

    return Command::SUCCESS;
})->purpose('Verify admin translation keys across en, ko, and zh');

Artisan::command('users:repair-account-status {--dry-run}', function (): int {
    $dryRun = (bool) $this->option('dry-run');
    $fixed = 0;
    $keptBanned = 0;
    $restored = 0;
    $ambiguous = 0;

    $users = DB::table('users')
        ->where('account_status', AccountStatus::Active->value)
        ->where('is_banned', true)
        ->orderBy('id')
        ->get();

    foreach ($users as $user) {
        $latestAction = DB::table('admin_action_logs')
            ->where('target_user_id', $user->id)
            ->where('action', 'user.account_status_updated')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $metadata = json_decode((string) ($latestAction->metadata ?? '{}'), true);
        $latestStatus = is_array($metadata) ? ($metadata['to'] ?? null) : null;

        if ($latestStatus === AccountStatus::Active->value) {
            $restored++;
            $fixed++;

            if (! $dryRun) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'is_banned' => false,
                        'banned_at' => null,
                        'ban_reason' => null,
                        'restricted_at' => null,
                        'restriction_reason' => null,
                        'updated_at' => now(),
                    ]);
            }

            continue;
        }

        if ($user->banned_at !== null) {
            $keptBanned++;
            $fixed++;

            if (! $dryRun) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'account_status' => AccountStatus::Banned->value,
                        'updated_at' => now(),
                    ]);
            }

            continue;
        }

        $ambiguous++;
        $fixed++;
        $this->warn("Ambiguous active/is_banned row without banned_at: user #{$user->id}. Clearing is_banned.");

        if (! $dryRun) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'is_banned' => false,
                    'ban_reason' => null,
                    'restricted_at' => null,
                    'restriction_reason' => null,
                    'updated_at' => now(),
                ]);
        }
    }

    $prefix = $dryRun ? '[dry-run] ' : '';
    $verb = $dryRun ? 'would be fixed' : 'fixed';
    $this->info("{$prefix}{$users->count()} inconsistent users found; {$fixed} {$verb}.");
    $this->line("{$prefix}{$keptBanned} kept banned, {$restored} restored active, {$ambiguous} ambiguous rows logged.");

    return Command::SUCCESS;
})->purpose('Repair inconsistent account_status and is_banned user rows');
