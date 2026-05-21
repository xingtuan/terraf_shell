<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Settings\SettingsService;
use App\Support\LegalHtmlSanitizer;
use App\Support\LocalizedContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LegalPageController extends Controller
{
    private const PAGES = ['privacy', 'terms'];

    private const FIELDS = [
        'metaTitle' => 'meta_title',
        'metaDescription' => 'meta_description',
        'eyebrow' => 'eyebrow',
        'title' => 'title',
        'description' => 'description',
        'lastUpdatedLabel' => 'last_updated_label',
        'lastUpdated' => 'last_updated',
        'bodyHtml' => 'body_html',
    ];

    public function show(string $page, Request $request, SettingsService $settings, LegalHtmlSanitizer $sanitizer): JsonResponse
    {
        if (! in_array($page, self::PAGES, true)) {
            abort(404);
        }

        $locale = LocalizedContent::resolveLocale($request->query('locale'));
        $content = [];

        foreach (self::FIELDS as $responseKey => $settingKey) {
            $value = $settings->string("legal.{$page}.{$locale}.{$settingKey}", '');

            if (trim($value) === '') {
                continue;
            }

            if ($settingKey === 'body_html' && $this->isBlankHtml($value)) {
                continue;
            }

            if ($settingKey === 'body_html') {
                $value = $sanitizer->sanitize($value);

                if ($this->isBlankHtml($value)) {
                    continue;
                }
            }

            $content[$responseKey] = $value;
        }

        return $this->successResponse($content);
    }

    private function isBlankHtml(string $value): bool
    {
        return trim(str_ireplace('&nbsp;', ' ', strip_tags($value))) === '';
    }
}
