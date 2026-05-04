<?php

namespace App\Services\Email;

use App\Jobs\Email\SendEmailEventJob;
use App\Mail\GenericEmailMessage;
use App\Models\EmailEvent;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmailDispatchService
{
    public function __construct(
        private readonly MailSettingsService $mailSettings,
        private readonly EmailTemplateRenderer $renderer,
    ) {}

    public function sendEvent(string $eventKey, array $payload, array $options = []): ?EmailLog
    {
        $settings = $this->mailSettings->getRuntimeSettings();

        if (! (bool) ($settings['is_enabled'] ?? false)) {
            return $this->skippedLog($eventKey, $payload, $options, 'global_disabled', $settings);
        }

        $event = EmailEvent::query()->where('key', $eventKey)->first();

        if (! $event instanceof EmailEvent) {
            return $this->skippedLog($eventKey, $payload, $options, 'event_missing', $settings);
        }

        if (! $event->is_enabled && ! ($options['force'] ?? false)) {
            return $this->skippedLog($eventKey, $payload, $options, 'event_disabled', $settings, $event);
        }

        $idempotencyKey = $options['idempotency_key'] ?? $this->defaultIdempotencyKey($eventKey, $payload, $options);

        if (filled($idempotencyKey)) {
            $existingLog = EmailLog::query()
                ->where('event_key', $eventKey)
                ->where('idempotency_key', $idempotencyKey)
                ->whereIn('status', [EmailLog::STATUS_QUEUED, EmailLog::STATUS_SENT])
                ->latest()
                ->first();

            if ($existingLog instanceof EmailLog) {
                return $existingLog;
            }
        }

        $recipients = $this->resolveRecipients($event, $payload, $options, $settings);

        if ($recipients === []) {
            return $this->skippedLog($eventKey, $payload, $options, 'no_recipients', $settings, $event);
        }

        if ($this->isThrottled($event, $payload, $recipients)) {
            return $this->skippedLog($eventKey, $payload, $options, 'throttled', $settings, $event);
        }

        $locale = $this->resolveLocale($payload, $options);
        $template = $this->resolveTemplate($event->template_key, $locale);

        if (! $template instanceof EmailTemplate) {
            return $this->skippedLog($eventKey, $payload, $options, 'template_missing', $settings, $event);
        }

        $rendered = $this->renderer->render([
            'subject' => $template->subject,
            'html_body' => $this->withPreheader($template->html_body, $template->preheader),
            'text_body' => $template->text_body,
        ], $this->enrichedPayload($payload));

        $log = EmailLog::query()->create([
            'event_key' => $event->key,
            'template_key' => $template->key,
            'locale' => $template->locale,
            'mailer' => $settings['mailer'] ?? null,
            'to' => $recipients,
            'cc' => $this->normalizeRecipients($options['cc'] ?? []),
            'bcc' => $this->normalizeRecipients($options['bcc'] ?? []),
            'subject' => $rendered['subject'],
            'status' => EmailLog::STATUS_QUEUED,
            'related_type' => $this->relatedModel($options) ? get_class($this->relatedModel($options)) : null,
            'related_id' => $this->relatedModel($options)?->getKey(),
            'user_id' => $this->payloadUser($payload)?->id,
            'payload' => $this->mailSettings->sanitizePayload(array_merge($this->serializePayload($payload), [
                '_rendered' => $rendered,
            ])),
            'idempotency_key' => $idempotencyKey,
            'queued_at' => now(),
        ]);

        $useQueue = (bool) ($options['queue'] ?? ($event->use_queue && ($settings['use_queue'] ?? true)));

        if (($options['sync'] ?? false) || ! $useQueue) {
            $this->sendLog($log);

            return $log->fresh();
        }

        DB::afterCommit(fn () => SendEmailEventJob::dispatch($log->id));

        return $log;
    }

    public function sendLog(EmailLog $log): EmailLog
    {
        if ($log->status === EmailLog::STATUS_SENT) {
            return $log;
        }

        try {
            $settings = $this->mailSettings->applyRuntimeConfig();
            $rendered = $log->payload['_rendered'] ?? null;

            if (! is_array($rendered)) {
                throw new \RuntimeException('Rendered email body is missing from email log.');
            }

            Mail::to($this->mailRecipients($log->to))
                ->cc($this->mailRecipients($log->cc ?? []))
                ->bcc($this->mailRecipients($log->bcc ?? []))
                ->send(new GenericEmailMessage(
                    (string) $rendered['subject'],
                    (string) $rendered['html'],
                    $rendered['text'] ?? null,
                    $settings['reply_to_address'] ?? null,
                    $settings['reply_to_name'] ?? null,
                ));

            $log->forceFill([
                'status' => EmailLog::STATUS_SENT,
                'sent_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ])->save();
        } catch (Throwable $throwable) {
            $log->forceFill([
                'status' => EmailLog::STATUS_FAILED,
                'failed_at' => now(),
                'error_message' => Str::limit($throwable->getMessage(), 2000, ''),
            ])->save();
        }

        return $log->fresh();
    }

    public function retry(EmailLog $log): EmailLog
    {
        if ($log->status !== EmailLog::STATUS_FAILED) {
            return $log;
        }

        $log->forceFill([
            'status' => EmailLog::STATUS_QUEUED,
            'queued_at' => now(),
            'failed_at' => null,
            'error_message' => null,
        ])->save();

        SendEmailEventJob::dispatch($log->id);

        return $log->fresh();
    }

    public function sendTest(string $email, ?User $actor = null): EmailLog
    {
        $key = 'email-center-test:'.($actor?->id ?: request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many test emails. Please wait before trying again.']);
        }

        RateLimiter::hit($key, 300);

        return $this->sendEvent('admin.test_email', [
            'user' => [
                'name' => $actor?->name ?: 'Admin',
                'email' => $email,
            ],
            'test' => [
                'email' => $email,
                'sent_by' => $actor?->email,
            ],
        ], [
            'to' => [$email],
            'sync' => true,
            'force' => true,
            'idempotency_key' => 'test:'.Str::uuid()->toString(),
        ]);
    }

    private function skippedLog(
        string $eventKey,
        array $payload,
        array $options,
        string $reason,
        array $settings,
        ?EmailEvent $event = null
    ): EmailLog {
        return EmailLog::query()->create([
            'event_key' => $eventKey,
            'template_key' => $event?->template_key,
            'locale' => $this->resolveLocale($payload, $options),
            'mailer' => $settings['mailer'] ?? null,
            'to' => $this->normalizeRecipients($options['to'] ?? []),
            'cc' => $this->normalizeRecipients($options['cc'] ?? []),
            'bcc' => $this->normalizeRecipients($options['bcc'] ?? []),
            'subject' => null,
            'status' => EmailLog::STATUS_SKIPPED,
            'skip_reason' => $reason,
            'related_type' => $this->relatedModel($options) ? get_class($this->relatedModel($options)) : null,
            'related_id' => $this->relatedModel($options)?->getKey(),
            'user_id' => $this->payloadUser($payload)?->id,
            'payload' => $this->mailSettings->sanitizePayload($this->serializePayload($payload)),
            'idempotency_key' => $options['idempotency_key'] ?? null,
        ]);
    }

    private function resolveRecipients(EmailEvent $event, array $payload, array $options, array $settings): array
    {
        if (array_key_exists('to', $options) && $options['to'] !== []) {
            return $this->normalizeRecipients($options['to']);
        }

        return match ($event->recipient_type) {
            'admin' => $this->adminRecipients($settings),
            'both' => $this->uniqueRecipients(array_merge(
                $this->userRecipients($payload),
                $this->adminRecipients($settings),
            )),
            'custom' => $this->normalizeRecipients($event->custom_recipients ?? []),
            default => $this->userRecipients($payload),
        };
    }

    private function userRecipients(array $payload): array
    {
        $user = $this->payloadUser($payload);

        if ($user instanceof User) {
            return $this->normalizeRecipients([[
                'email' => $user->email,
                'name' => $user->name,
            ]]);
        }

        $email = data_get($payload, 'user.email') ?: data_get($payload, 'inquiry.email');

        if (blank($email)) {
            return [];
        }

        return $this->normalizeRecipients([[
            'email' => $email,
            'name' => data_get($payload, 'user.name') ?: data_get($payload, 'inquiry.name'),
        ]]);
    }

    private function adminRecipients(array $settings): array
    {
        $configured = $this->normalizeRecipients($settings['admin_recipients'] ?? []);

        if ($configured !== []) {
            return $configured;
        }

        return User::query()
            ->where('role', 'admin')
            ->where('account_status', 'active')
            ->where('is_banned', false)
            ->pluck('email')
            ->map(fn (string $email): array => ['email' => $email, 'name' => null])
            ->all();
    }

    private function normalizeRecipients(mixed $recipients): array
    {
        return collect(Arr::wrap($recipients))
            ->map(function (mixed $recipient): ?array {
                if (is_string($recipient)) {
                    $email = trim($recipient);

                    return filter_var($email, FILTER_VALIDATE_EMAIL)
                        ? ['email' => $email, 'name' => null]
                        : null;
                }

                if (is_array($recipient)) {
                    $email = trim((string) ($recipient['email'] ?? $recipient['address'] ?? ''));

                    return filter_var($email, FILTER_VALIDATE_EMAIL)
                        ? ['email' => $email, 'name' => filled($recipient['name'] ?? null) ? (string) $recipient['name'] : null]
                        : null;
                }

                if ($recipient instanceof User && filter_var($recipient->email, FILTER_VALIDATE_EMAIL)) {
                    return ['email' => $recipient->email, 'name' => $recipient->name];
                }

                return null;
            })
            ->filter()
            ->unique(fn (array $recipient): string => Str::lower($recipient['email']))
            ->values()
            ->all();
    }

    private function uniqueRecipients(array $recipients): array
    {
        return collect($recipients)
            ->unique(fn (array $recipient): string => Str::lower($recipient['email']))
            ->values()
            ->all();
    }

    private function mailRecipients(?array $recipients): array
    {
        $mailRecipients = [];

        foreach ($recipients ?? [] as $recipient) {
            if (filled($recipient['name'] ?? null)) {
                $mailRecipients[$recipient['email']] = $recipient['name'];
            } else {
                $mailRecipients[] = $recipient['email'];
            }
        }

        return $mailRecipients;
    }

    private function resolveLocale(array $payload, array $options): string
    {
        $locale = Str::lower((string) (
            $options['locale']
            ?? data_get($payload, 'user.locale')
            ?? data_get($payload, 'locale')
            ?? app()->getLocale()
            ?? 'en'
        ));

        return in_array($locale, ['en', 'zh', 'ko'], true) ? $locale : 'en';
    }

    private function resolveTemplate(string $templateKey, string $locale): ?EmailTemplate
    {
        return EmailTemplate::query()
            ->where('key', $templateKey)
            ->where('locale', $locale)
            ->where('is_active', true)
            ->first()
            ?: EmailTemplate::query()
                ->where('key', $templateKey)
                ->where('locale', 'en')
                ->where('is_active', true)
                ->first();
    }

    private function enrichedPayload(array $payload): array
    {
        return array_replace_recursive([
            'app' => [
                'name' => config('app.name', 'OXP'),
                'url' => config('app.url'),
            ],
        ], $payload);
    }

    private function serializePayload(array $payload): array
    {
        return collect($payload)->map(function (mixed $value): mixed {
            if ($value instanceof User) {
                return [
                    'id' => $value->id,
                    'name' => $value->name,
                    'email' => $value->email,
                ];
            }

            if ($value instanceof Model) {
                return [
                    'type' => get_class($value),
                    'id' => $value->getKey(),
                ];
            }

            if (is_array($value)) {
                return $this->serializePayload($value);
            }

            return $value;
        })->all();
    }

    private function payloadUser(array $payload): ?User
    {
        return ($payload['user'] ?? null) instanceof User ? $payload['user'] : null;
    }

    private function relatedModel(array $options): ?Model
    {
        return ($options['related'] ?? null) instanceof Model ? $options['related'] : null;
    }

    private function defaultIdempotencyKey(string $eventKey, array $payload, array $options): ?string
    {
        if (Str::startsWith($eventKey, ['order.', 'auth.'])) {
            $related = $this->relatedModel($options);
            $user = $this->payloadUser($payload);

            return collect([
                $related?->getMorphClass(),
                $related?->getKey(),
                $user?->id,
                $eventKey,
            ])->filter(fn ($value): bool => filled($value))->implode(':') ?: null;
        }

        return null;
    }

    private function isThrottled(EmailEvent $event, array $payload, array $recipients): bool
    {
        if ($event->throttle_minutes === null || $event->throttle_minutes <= 0) {
            return false;
        }

        $query = EmailLog::query()
            ->where('event_key', $event->key)
            ->whereIn('status', [EmailLog::STATUS_QUEUED, EmailLog::STATUS_SENT])
            ->where('created_at', '>=', now()->subMinutes($event->throttle_minutes));

        $user = $this->payloadUser($payload);

        if ($user instanceof User) {
            $query->where('user_id', $user->id);
        } else {
            $email = $recipients[0]['email'] ?? null;
            $query->where('to', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $email).'%');
        }

        return $query->exists();
    }

    private function withPreheader(string $html, ?string $preheader): string
    {
        if (blank($preheader)) {
            return $html;
        }

        return '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">'.e($preheader).'</div>'.$html;
    }
}
