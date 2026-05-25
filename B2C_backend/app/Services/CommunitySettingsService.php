<?php

namespace App\Services;

use App\Enums\CommunitySubmissionPolicy;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Str;

class CommunitySettingsService
{
    /**
     * @var list<string>
     */
    private const DEFAULT_ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
        'pdf',
        'doc',
        'docx',
        'ppt',
        'pptx',
        'xls',
        'xlsx',
        'txt',
        'md',
        'csv',
        'zip',
        'rar',
        '7z',
        'stl',
        'obj',
        'glb',
        'gltf',
        'dwg',
        'dxf',
        'step',
        'stp',
        'iges',
        'igs',
        'srt',
    ];

    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function allowGuestUpload(): bool
    {
        return $this->settings->boolean(
            'community.allow_guest_upload',
            (bool) config('community.uploads.allow_guest_upload', false),
        );
    }

    public function maxFiles(): int
    {
        return $this->minimumInteger(
            $this->settings->get('community.max_files', config('community.idea_media.max_files', 12)),
            1,
            12,
        );
    }

    public function maxFileSizeKb(): int
    {
        return $this->minimumInteger(
            $this->settings->get('community.max_file_size_kb', config('community.idea_media.max_file_size_kb', 10240)),
            1,
            10240,
        );
    }

    /**
     * @return list<string>
     */
    public function allowedExtensions(): array
    {
        $value = $this->settings->get(
            'community.allowed_extensions',
            config('community.idea_media.allowed_extensions', self::DEFAULT_ALLOWED_EXTENSIONS),
        );

        $extensions = $this->normalizeList($value)
            ->map(static fn (string $extension): string => strtolower(trim(ltrim($extension, '.'))))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $extensions !== [] ? $extensions : self::DEFAULT_ALLOWED_EXTENSIONS;
    }

    public function maxExternalLinks(): int
    {
        return $this->minimumInteger(
            $this->settings->get('community.max_external_links', config('community.idea_media.max_external_links', 4)),
            0,
            4,
        );
    }

    public function sensitiveWordsEnabled(): bool
    {
        return $this->settings->boolean(
            'community.sensitive_words_enabled',
            (bool) config('community.moderation.sensitive_words.enabled', false),
        );
    }

    /**
     * @return list<string>
     */
    public function sensitiveWords(): array
    {
        $value = $this->settings->get(
            'community.sensitive_words',
            config('community.moderation.sensitive_words.terms', []),
        );

        return $this->normalizeList($value)
            ->map(static fn (string $term): string => trim($term))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function normalizeSubmissionPolicy(string $value): string
    {
        return match (Str::of($value)->trim()->lower()->value()) {
            CommunitySubmissionPolicy::AllAutoApprove->value,
            'auto_publish',
            'open' => CommunitySubmissionPolicy::AllAutoApprove->value,
            CommunitySubmissionPolicy::TrustedUsersAutoApprove->value,
            'trusted_auto_publish' => CommunitySubmissionPolicy::TrustedUsersAutoApprove->value,
            CommunitySubmissionPolicy::AllRequireApproval->value,
            'require_approval',
            'manual' => CommunitySubmissionPolicy::AllRequireApproval->value,
            default => CommunitySubmissionPolicy::AllRequireApproval->value,
        };
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function normalizeList(mixed $value): \Illuminate\Support\Collection
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : preg_split('/[\r\n,]+/', $value);
        }

        if (! is_array($value)) {
            $value = [$value];
        }

        return collect($value)
            ->flatten()
            ->flatMap(static function (mixed $item): array {
                $text = trim((string) $item);

                if ($text === '') {
                    return [];
                }

                $decoded = json_decode($text, true);

                if (is_array($decoded)) {
                    return collect($decoded)
                        ->flatten()
                        ->map(static fn (mixed $decodedItem): string => trim((string) $decodedItem))
                        ->all();
                }

                return preg_split('/[\r\n,]+/', $text) ?: [];
            })
            ->map(static fn (mixed $item): string => trim((string) $item));
    }

    private function minimumInteger(mixed $value, int $minimum, int $fallback): int
    {
        if (! is_numeric($value)) {
            return $fallback;
        }

        return max($minimum, (int) $value);
    }
}
