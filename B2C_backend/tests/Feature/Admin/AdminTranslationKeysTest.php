<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminTranslationKeysTest extends TestCase
{
    public function test_admin_language_files_have_identical_key_structures(): void
    {
        $baseKeys = $this->flattenKeys(require lang_path('en/admin.php'));

        foreach (['ko', 'zh'] as $locale) {
            $localeKeys = $this->flattenKeys(require lang_path("{$locale}/admin.php"));

            $this->assertSame(
                [],
                array_values(array_diff($baseKeys, $localeKeys)),
                "{$locale} admin translations are missing keys from en/admin.php."
            );

            $this->assertSame(
                [],
                array_values(array_diff($localeKeys, $baseKeys)),
                "{$locale} admin translations contain keys not present in en/admin.php."
            );
        }
    }

    /**
     * @param  array<string, mixed>  $items
     * @return array<int, string>
     */
    private function flattenKeys(array $items, string $prefix = ''): array
    {
        $keys = [];

        foreach ($items as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                array_push($keys, ...$this->flattenKeys($value, $fullKey));

                continue;
            }

            $keys[] = $fullKey;
        }

        sort($keys);

        return $keys;
    }
}
