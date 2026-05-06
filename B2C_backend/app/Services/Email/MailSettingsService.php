<?php

namespace App\Services\Email;

use App\Models\EmailSetting;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MailSettingsService
{
    public const MAILERS = [
        'smtp',
        'sendmail',
        'mailgun',
        'ses',
        'postmark',
        'resend',
        'log',
        'array',
    ];

    public function getDatabaseSettings(): ?EmailSetting
    {
        return EmailSetting::query()->orderBy('id')->first();
    }

    public function getRuntimeSettings(): array
    {
        $settings = $this->getDatabaseSettings();

        if ($settings instanceof EmailSetting) {
            return [
                'source' => 'database',
                'is_enabled' => (bool) $settings->is_enabled,
                'mailer' => $settings->mailer,
                'host' => $settings->host,
                'port' => $settings->port,
                'encryption' => $settings->encryption,
                'username' => $settings->username,
                'password' => $settings->password,
                'api_key' => $settings->api_key,
                'domain' => $settings->domain,
                'region' => $settings->region,
                'from_address' => $settings->from_address,
                'from_name' => $settings->from_name,
                'reply_to_address' => $settings->reply_to_address,
                'reply_to_name' => $settings->reply_to_name,
                'admin_recipients' => $settings->admin_recipients ?? [],
                'timeout' => $settings->timeout,
                'use_queue' => (bool) $settings->use_queue,
            ];
        }

        return [
            'source' => 'config',
            'is_enabled' => false,
            'mailer' => (string) config('mail.default', 'log'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.scheme'),
            'username' => config('mail.mailers.smtp.username'),
            'password' => config('mail.mailers.smtp.password'),
            'api_key' => config('services.resend.key') ?: config('services.postmark.key'),
            'domain' => config('services.mailgun.domain'),
            'region' => config('services.ses.region'),
            'from_address' => (string) config('mail.from.address'),
            'from_name' => (string) config('mail.from.name'),
            'reply_to_address' => null,
            'reply_to_name' => null,
            'admin_recipients' => [],
            'timeout' => config('mail.mailers.smtp.timeout'),
            'use_queue' => true,
        ];
    }

    public function applyRuntimeConfig(?array $settings = null): array
    {
        $settings ??= $this->getRuntimeSettings();
        $mailer = (string) ($settings['mailer'] ?? 'log');

        config([
            'mail.default' => $mailer,
            'mail.from.address' => $settings['from_address'],
            'mail.from.name' => $settings['from_name'],
        ]);

        if ($mailer === 'smtp') {
            config([
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $settings['host'],
                'mail.mailers.smtp.port' => $settings['port'],
                'mail.mailers.smtp.username' => $settings['username'],
                'mail.mailers.smtp.password' => $settings['password'],
                'mail.mailers.smtp.timeout' => $settings['timeout'],
                'mail.mailers.smtp.scheme' => $settings['encryption'],
                'mail.mailers.smtp.encryption' => $settings['encryption'],
            ]);
        }

        if (in_array($mailer, ['log', 'array', 'sendmail'], true)) {
            config(["mail.mailers.{$mailer}.transport" => $mailer]);
        }

        if ($mailer === 'mailgun') {
            config([
                'mail.mailers.mailgun.transport' => 'mailgun',
                'services.mailgun.domain' => $settings['domain'],
                'services.mailgun.secret' => $settings['api_key'],
            ]);
        }

        if ($mailer === 'ses') {
            config([
                'mail.mailers.ses.transport' => 'ses',
                'services.ses.key' => $settings['username'] ?: config('services.ses.key'),
                'services.ses.secret' => $settings['api_key'] ?: config('services.ses.secret'),
                'services.ses.region' => $settings['region'] ?: config('services.ses.region'),
            ]);
        }

        if ($mailer === 'postmark') {
            config([
                'mail.mailers.postmark.transport' => 'postmark',
                'services.postmark.key' => $settings['api_key'],
            ]);
        }

        if ($mailer === 'resend') {
            config([
                'mail.mailers.resend.transport' => 'resend',
                'services.resend.key' => $settings['api_key'],
            ]);
        }

        app('mail.manager')->forgetMailers();

        return $settings;
    }

    public function validate(array $data): void
    {
        $mailer = (string) ($data['mailer'] ?? 'log');

        if (! in_array($mailer, self::MAILERS, true)) {
            throw ValidationException::withMessages(['mailer' => 'Unsupported mailer selected.']);
        }

        if (in_array($mailer, ['smtp'], true)) {
            foreach (['host', 'port', 'from_address', 'from_name'] as $field) {
                if (blank($data[$field] ?? null)) {
                    throw ValidationException::withMessages([$field => 'This field is required for SMTP mail.']);
                }
            }
        }

        if (in_array($mailer, ['mailgun'], true) && blank($data['domain'] ?? null)) {
            throw ValidationException::withMessages(['domain' => 'A domain is required for Mailgun.']);
        }

        if (! filter_var((string) ($data['from_address'] ?? ''), FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['from_address' => 'The sender email address is invalid.']);
        }

        foreach (Arr::wrap($data['admin_recipients'] ?? []) as $email) {
            if (filled($email) && ! filter_var((string) $email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages(['admin_recipients' => 'Admin recipients must be valid email addresses.']);
            }
        }
    }

    public function save(array $data, ?User $actor = null): EmailSetting
    {
        $this->validate($data);

        $settings = $this->getDatabaseSettings();
        $creating = ! $settings instanceof EmailSetting;
        $settings ??= new EmailSetting;

        if ($creating && $actor instanceof User) {
            $settings->created_by_id = $actor->id;
        }

        $settings->fill(Arr::except($data, ['password', 'api_key']));

        if (array_key_exists('password', $data) && filled($data['password'])) {
            $settings->password = (string) $data['password'];
        }

        if (array_key_exists('api_key', $data) && filled($data['api_key'])) {
            $settings->api_key = (string) $data['api_key'];
        }

        if ($actor instanceof User) {
            $settings->updated_by_id = $actor->id;
        }

        $settings->save();

        return $settings;
    }

    public function maskedState(): array
    {
        $settings = $this->getDatabaseSettings();

        if (! $settings instanceof EmailSetting) {
            return [
                'is_enabled' => false,
                'mailer' => (string) config('mail.default', 'log'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.scheme'),
                'username' => config('mail.mailers.smtp.username'),
                'password' => null,
                'api_key' => null,
                'domain' => config('services.mailgun.domain'),
                'region' => config('services.ses.region'),
                'from_address' => (string) config('mail.from.address'),
                'from_name' => (string) config('mail.from.name'),
                'reply_to_address' => null,
                'reply_to_name' => null,
                'admin_recipients' => [],
                'timeout' => null,
                'use_queue' => true,
            ];
        }

        return array_merge($settings->only([
            'is_enabled',
            'mailer',
            'host',
            'port',
            'encryption',
            'username',
            'domain',
            'region',
            'from_address',
            'from_name',
            'reply_to_address',
            'reply_to_name',
            'admin_recipients',
            'timeout',
            'use_queue',
        ]), [
            'password' => $settings->maskedPassword(),
            'api_key' => $settings->maskedApiKey(),
        ]);
    }

    public function sanitizePayload(array $payload): array
    {
        foreach (['password', 'api_key', 'token', 'secret'] as $secretKey) {
            if (array_key_exists($secretKey, $payload)) {
                $payload[$secretKey] = '[masked]';
            }
        }

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->sanitizePayload($value);
            } elseif (Str::contains(Str::lower((string) $key), ['password', 'secret', 'token', 'api_key'])) {
                $payload[$key] = '[masked]';
            }
        }

        return $payload;
    }
}
