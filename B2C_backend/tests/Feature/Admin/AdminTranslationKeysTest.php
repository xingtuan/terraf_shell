<?php

namespace Tests\Feature\Admin;

use App\Filament\Support\HasAdminResourceTranslations;
use Filament\Resources\Resource;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use SplFileInfo;
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

    public function test_filament_resources_have_admin_navigation_translation_keys(): void
    {
        $originalLocale = app()->getLocale();

        try {
            foreach ($this->filamentResourceClasses() as $resourceClass) {
                $this->assertContains(
                    HasAdminResourceTranslations::class,
                    class_uses_recursive($resourceClass),
                    "{$resourceClass} must use HasAdminResourceTranslations."
                );

                $method = new ReflectionMethod($resourceClass, 'adminResourceTranslationKey');
                $method->setAccessible(true);

                $translationKey = $method->invoke(null, 'navigation');

                $this->assertIsString($translationKey, "{$resourceClass} is missing a navigation translation key.");
                $this->assertStringStartsWith('admin.resources.', $translationKey);

                foreach (['en', 'ko', 'zh'] as $locale) {
                    app()->setLocale($locale);

                    $this->assertNotSame(
                        $translationKey,
                        __($translationKey),
                        "{$resourceClass} navigation key {$translationKey} is missing for {$locale}."
                    );
                }
            }
        } finally {
            app()->setLocale($originalLocale);
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

    /**
     * @return array<int, class-string<resource>>
     */
    private function filamentResourceClasses(): array
    {
        $path = app_path('Filament/Resources');
        $classes = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || ! str_ends_with($file->getFilename(), 'Resource.php')) {
                continue;
            }

            $relativePath = substr($file->getPathname(), strlen($path) + 1, -4);
            $class = 'App\\Filament\\Resources\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

            if (class_exists($class) && is_subclass_of($class, Resource::class)) {
                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }
}
