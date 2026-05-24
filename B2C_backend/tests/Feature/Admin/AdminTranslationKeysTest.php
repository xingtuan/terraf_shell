<?php

namespace Tests\Feature\Admin;

use App\Enums\PublishStatus;
use App\Filament\Support\HasAdminResourceTranslations;
use App\Support\HomeSectionVisibility;
use Filament\Resources\Resource;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use SplFileInfo;
use Tests\TestCase;

class AdminTranslationKeysTest extends TestCase
{
    public function test_home_section_visibility_labels_are_resource_specific(): void
    {
        $originalLocale = app()->getLocale();

        try {
            $expected = [
                'en' => [
                    'visible' => 'Visible on frontend',
                    'hidden' => 'Hidden from frontend',
                    'archived' => 'Archived / hidden',
                    'column' => 'Frontend visibility',
                    'toggle' => 'Show on frontend',
                    'help' => 'Turn off to hide this whole section from the public website.',
                ],
                'zh' => [
                    'visible' => '在前端显示',
                    'hidden' => '不显示',
                    'archived' => '已归档 / 不显示',
                    'column' => '前端显示状态',
                    'toggle' => '在前端显示',
                    'help' => '关闭后，该整个板块不会在前端网站显示。',
                ],
                'ko' => [
                    'visible' => '프론트엔드에 표시',
                    'hidden' => '프론트엔드에서 숨김',
                    'archived' => '보관됨 / 숨김',
                    'column' => '프론트엔드 표시 상태',
                    'toggle' => '프론트엔드에 표시',
                    'help' => '끄면 이 전체 섹션이 공개 웹사이트에 표시되지 않습니다.',
                ],
            ];

            foreach ($expected as $locale => $labels) {
                app()->setLocale($locale);

                $this->assertSame($labels['visible'], HomeSectionVisibility::labelFor(PublishStatus::Published));
                $this->assertSame($labels['hidden'], HomeSectionVisibility::labelFor(PublishStatus::Draft));
                $this->assertSame($labels['archived'], HomeSectionVisibility::labelFor(PublishStatus::Archived));
                $this->assertSame($labels['column'], __('admin.home_sections.columns.frontend_visibility'));
                $this->assertSame($labels['column'], __('admin.home_sections.filters.frontend_visibility'));
                $this->assertSame($labels['toggle'], __('admin.home_sections.fields.show_on_frontend'));
                $this->assertSame($labels['help'], __('admin.home_sections.help.show_on_frontend'));
            }

            app()->setLocale('en');

            $this->assertSame('Published', __('admin.publish_status.published'));
            $this->assertSame('Visible on frontend', HomeSectionVisibility::options()[PublishStatus::Published->value]);
        } finally {
            app()->setLocale($originalLocale);
        }
    }

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
