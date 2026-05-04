<?php

namespace App\Services\Email;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class EmailTemplateRenderer
{
    /**
     * @return array{subject: string, html: string, text: ?string}
     */
    public function render(array $template, array $payload): array
    {
        return [
            'subject' => $this->renderString((string) $template['subject'], $payload, false),
            'html' => $this->renderHtml((string) $template['html_body'], $payload),
            'text' => filled($template['text_body'] ?? null)
                ? $this->renderString((string) $template['text_body'], $payload, false)
                : $this->htmlToText($this->renderHtml((string) $template['html_body'], $payload)),
        ];
    }

    public function renderHtml(string $template, array $payload): string
    {
        $withoutUnsafeDirectives = $this->stripExecutableSyntax($template);

        $rendered = preg_replace_callback('/\{\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}\}/', function (array $matches) use ($payload): string {
            return $this->stringify($this->lookup($payload, $matches[1]), true);
        }, $withoutUnsafeDirectives);

        return preg_replace_callback('/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/', function (array $matches) use ($payload): string {
            return $this->stringify($this->lookup($payload, $matches[1]), true, true);
        }, $rendered);
    }

    public function renderString(string $template, array $payload, bool $isHtml = false): string
    {
        $withoutUnsafeDirectives = $this->stripExecutableSyntax($template);

        return preg_replace_callback('/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/', function (array $matches) use ($payload, $isHtml): string {
            return $this->stringify($this->lookup($payload, $matches[1]), $isHtml, $isHtml);
        }, $withoutUnsafeDirectives);
    }

    public function samplePayload(string $eventKey): array
    {
        $base = [
            'app' => [
                'name' => config('app.name', 'OXP'),
                'url' => config('app.url', 'https://example.com'),
            ],
            'user' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
            ],
            'actor' => [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
            ],
            'action_url' => config('app.url', 'https://example.com'),
        ];

        return array_replace_recursive($base, match (true) {
            Str::startsWith($eventKey, 'auth.email_verification') => [
                'verification_url' => config('app.url', 'https://example.com').'/verify/sample',
            ],
            $eventKey === 'auth.password_reset' => [
                'reset_url' => config('app.url', 'https://example.com').'/reset-password?token=sample',
                'expires_minutes' => 60,
            ],
            Str::startsWith($eventKey, 'order.') => [
                'order' => [
                    'order_number' => 'SHF-000123',
                    'status' => 'pending',
                    'total' => '$128.00',
                    'currency' => 'USD',
                    'items' => [
                        ['name' => 'Shellfin Panel', 'quantity' => 2, 'subtotal' => '$128.00'],
                    ],
                ],
                'order_url' => config('app.url', 'https://example.com').'/account/orders/SHF-000123',
                'shipping' => ['address' => '12 Queen Street, Auckland, NZ'],
            ],
            Str::contains($eventKey, ['inquiry.', 'lead.', 'b2b_lead.', 'partnership_inquiry.', 'sample_request.']) => [
                'inquiry' => [
                    'name' => 'Mina Park',
                    'email' => 'mina@example.com',
                    'company' => 'Design Lab',
                    'message' => 'We would like to discuss material samples.',
                    'type' => 'Sample request',
                ],
                'lead' => [
                    'reference' => 'INQ-000123',
                    'status' => 'new',
                ],
            ],
            Str::startsWith($eventKey, 'community.') => [
                'post' => [
                    'title' => 'Low-waste lamp concept',
                    'slug' => 'low-waste-lamp',
                ],
                'post_url' => config('app.url', 'https://example.com').'/posts/low-waste-lamp',
                'moderator' => ['name' => 'Admin User'],
                'reason' => 'Please add more build details.',
                'edit_url' => config('app.url', 'https://example.com').'/posts/low-waste-lamp/edit',
            ],
            default => [],
        });
    }

    private function lookup(array $payload, string $path): mixed
    {
        return Arr::get($payload, $path, '');
    }

    private function stringify(mixed $value, bool $isHtml = false, bool $escape = false): string
    {
        if ($value instanceof HtmlString) {
            return $value->toHtml();
        }

        if (is_array($value)) {
            $value = $this->arrayToString($value, $isHtml);
        }

        if (is_bool($value)) {
            $value = $value ? 'Yes' : 'No';
        }

        $value = (string) ($value ?? '');

        return $escape ? e($value) : $value;
    }

    private function arrayToString(array $value, bool $isHtml): string
    {
        if ($value === []) {
            return '';
        }

        $lines = collect($value)->map(function (mixed $item, mixed $key) use ($isHtml): string {
            if (is_array($item)) {
                $item = collect($item)
                    ->map(fn (mixed $nested, mixed $nestedKey): string => Str::headline((string) $nestedKey).': '.(is_scalar($nested) ? (string) $nested : json_encode($nested)))
                    ->implode(', ');
            }

            $line = is_int($key) ? (string) $item : Str::headline((string) $key).': '.(string) $item;

            return $isHtml ? e($line) : $line;
        })->all();

        return $isHtml
            ? '<ul><li>'.implode('</li><li>', $lines).'</li></ul>'
            : implode("\n", $lines);
    }

    private function stripExecutableSyntax(string $template): string
    {
        $template = preg_replace('/<\?(?:php)?[\s\S]*?\?>/i', '', $template);
        $template = preg_replace('/@(?:if|foreach|for|while|php|endphp|include|extends|section|yield|csrf|method|auth|guest|isset|empty|switch|case|default|break|continue|endforeach|endif|endfor|endwhile|endsection|endswitch)\b(?:\s*\([^)]*\))?/i', '', $template);

        return preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $template);
    }

    private function htmlToText(string $html): string
    {
        return trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], "\n", $html))));
    }
}
