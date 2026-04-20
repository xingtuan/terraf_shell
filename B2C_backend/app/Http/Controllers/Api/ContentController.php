<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteSectionResource;
use App\Models\SiteSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ContentController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const PAGES = [
        'home',
        'material',
        'store',
        'b2b',
        'community',
    ];

    public function page(Request $request, string $page): JsonResponse
    {
        $validator = Validator::make(
            ['page' => $page],
            ['page' => ['required', Rule::in(self::PAGES)]]
        );

        if ($validator->fails()) {
            return $this->errorResponse(
                $validator->errors()->first(),
                $validator->errors()->toArray()
            );
        }

        $locale = $this->resolveLocale($request);
        $sections = SiteSection::query()
            ->active()
            ->forLocale($locale)
            ->where('page', $page)
            ->orderBy('sort_order')
            ->get();

        $payload = $sections
            ->mapWithKeys(fn (SiteSection $section): array => [
                $section->section => (new SiteSectionResource($section))->resolve($request),
            ])
            ->all();

        return $this->successResponse($payload);
    }

    private function resolveLocale(Request $request): string
    {
        $locale = (string) ($request->query('locale') ?? $request->header('Accept-Language') ?? 'en');
        $resolved = substr($locale, 0, 2);

        return in_array($resolved, ['en', 'ko', 'zh'], true) ? $resolved : 'en';
    }
}
