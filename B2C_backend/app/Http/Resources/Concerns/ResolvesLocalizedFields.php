<?php

namespace App\Http\Resources\Concerns;

use App\Support\LocalizedContent;
use Illuminate\Http\Request;

trait ResolvesLocalizedFields
{
    protected function locale(Request $request): string
    {
        return LocalizedContent::resolveLocale(
            $request->query('locale', $request->header('X-Locale'))
        );
    }

    protected function localizedString(Request $request, string $attribute): ?string
    {
        return LocalizedContent::resolveString(
            $this->{$attribute.'_translations'} ?? null,
            $this->locale($request),
            $this->{$attribute} ?? null,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function localizedStringSet(string $attribute): array
    {
        return LocalizedContent::normalizeStringTranslations(
            $this->{$attribute.'_translations'} ?? null,
            $this->{$attribute} ?? null,
        );
    }

    /**
     * @return array<int, string>
     */
    protected function localizedArray(Request $request, string $attribute): array
    {
        $fallback = $this->{$attribute};

        return LocalizedContent::resolveArray(
            $this->{$attribute.'_translations'} ?? null,
            $this->locale($request),
            is_array($fallback) ? $fallback : [],
        );
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function localizedArraySet(string $attribute): array
    {
        $fallback = $this->{$attribute};

        return LocalizedContent::normalizeArrayTranslations(
            $this->{$attribute.'_translations'} ?? null,
            is_array($fallback) ? $fallback : [],
        );
    }
}
