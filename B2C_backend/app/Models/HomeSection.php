<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Concerns\HasOptionalMediaUrl;
use App\Models\Concerns\HasPublishStatus;
use App\Support\HomeSectionPayloadNormalizer;
use Database\Factories\HomeSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    /** @use HasFactory<HomeSectionFactory> */
    use HasFactory, HasLocalizedAttributes, HasOptionalMediaUrl, HasPublishStatus;

    public const PAGE_KEY_OPTIONS = [
        'home' => 'Home',
        'material' => 'Material',
        'store' => 'Store',
        'community' => 'Community',
        'b2b' => 'B2B',
        'contact' => 'Contact',
        'articles' => 'Articles',
    ];

    protected array $localizedAttributes = [
        'title',
        'subtitle',
        'content',
        'cta_label',
    ];

    protected $fillable = [
        'page_key',
        'key',
        'title',
        'title_translations',
        'subtitle',
        'subtitle_translations',
        'content',
        'content_translations',
        'cta_label',
        'cta_label_translations',
        'cta_url',
        'payload',
        'is_seeded',
        'status',
        'sort_order',
        'media_path',
        'media_url',
        'published_at',
    ];

    protected $attributes = [
        'page_key' => 'home',
    ];

    /**
     * @return array<string, string>
     */
    public static function pageKeyOptions(): array
    {
        $options = [];

        foreach (self::PAGE_KEY_OPTIONS as $key => $label) {
            $options[$key] = self::pageKeyLabel($key, $label);
        }

        return $options;
    }

    public static function pageKeyLabel(?string $key, ?string $fallback = null): string
    {
        if (is_string($key) && array_key_exists($key, self::PAGE_KEY_OPTIONS)) {
            $translationKey = "admin.home_sections.pages.{$key}";
            $translation = __($translationKey);

            return $translation === $translationKey
                ? self::PAGE_KEY_OPTIONS[$key]
                : $translation;
        }

        return $fallback ?? (is_string($key) ? $key : '');
    }

    /**
     * @return array<int, string>
     */
    public static function allowedPageKeys(): array
    {
        return array_keys(self::PAGE_KEY_OPTIONS);
    }

    protected function casts(): array
    {
        return [
            'title_translations' => 'array',
            'subtitle_translations' => 'array',
            'content_translations' => 'array',
            'cta_label_translations' => 'array',
            'payload' => 'array',
            'is_seeded' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function setPayloadAttribute(mixed $value): void
    {
        $this->attributes['payload'] = $value === null
            ? null
            : json_encode(
                HomeSectionPayloadNormalizer::normalize($value),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
    }
}
