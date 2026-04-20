<?php

namespace App\Models\Concerns;

use App\Support\LocalizedContent;

trait HasLocalizedAttributes
{
    protected static function bootHasLocalizedAttributes(): void
    {
        static::saving(function ($model): void {
            $model->syncLocalizedAttributes();
        });
    }

    protected function syncLocalizedAttributes(): void
    {
        foreach ($this->localizedAttributeTypes() as $attribute => $type) {
            if (is_int($attribute)) {
                $attribute = $type;
                $type = 'string';
            }

            $translationColumn = $attribute.'_translations';

            if ($type === 'array') {
                $currentValue = $this->getAttribute($attribute);
                $currentArray = is_array($currentValue) ? $currentValue : [];
                $translations = LocalizedContent::normalizeArrayTranslations(
                    $this->getAttribute($translationColumn),
                    $currentArray
                );

                if ($this->isDirty($attribute) && ! $this->isDirty($translationColumn) && $currentArray !== []) {
                    $translations[LocalizedContent::DEFAULT_LOCALE] = array_values($currentArray);
                }

                $this->setAttribute($translationColumn, $translations === [] ? null : $translations);
                $this->setAttribute(
                    $attribute,
                    LocalizedContent::resolveArray($translations, config('app.locale'), $currentArray)
                );

                continue;
            }

            $currentValue = $this->getAttribute($attribute);
            $fallback = is_string($currentValue) ? $currentValue : null;
            $translations = LocalizedContent::normalizeStringTranslations(
                $this->getAttribute($translationColumn),
                $fallback
            );

            if (
                $this->isDirty($attribute)
                && ! $this->isDirty($translationColumn)
                && is_string($currentValue)
                && trim($currentValue) !== ''
            ) {
                $translations[LocalizedContent::DEFAULT_LOCALE] = trim($currentValue);
            }

            $this->setAttribute($translationColumn, $translations === [] ? null : $translations);
            $this->setAttribute(
                $attribute,
                LocalizedContent::resolveString($translations, config('app.locale'), $fallback)
            );
        }
    }

    /**
     * @return array<int|string, string>
     */
    protected function localizedAttributeTypes(): array
    {
        if (property_exists($this, 'localizedAttributes')) {
            /** @var array<int|string, string> $localizedAttributes */
            $localizedAttributes = $this->localizedAttributes;

            return $localizedAttributes;
        }

        return [];
    }
}
