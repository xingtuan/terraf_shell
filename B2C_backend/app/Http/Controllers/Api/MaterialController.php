<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CertificationResource;
use App\Http\Resources\MaterialPropertyResource;
use App\Http\Resources\ProcessStepResource;
use App\Http\Resources\SiteSectionResource;
use App\Models\Certification;
use App\Models\MaterialProperty;
use App\Models\ProcessStep;
use App\Models\SiteSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse($this->payload($request));
    }

    public function show(Request $request, string $identifier): JsonResponse
    {
        return $this->successResponse($this->payload($request));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        $sections = SiteSection::query()
            ->active()
            ->forLocale($locale)
            ->where('page', 'material')
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (SiteSection $section): array => [
                $section->section => (new SiteSectionResource($section))->resolve($request),
            ])
            ->all();

        return [
            'sections' => $sections,
            'properties' => MaterialPropertyResource::collection(
                MaterialProperty::query()
                    ->active()
                    ->forLocale($locale)
                    ->orderBy('sort_order')
                    ->get()
            )->resolve($request),
            'process' => ProcessStepResource::collection(
                ProcessStep::query()
                    ->active()
                    ->forLocale($locale)
                    ->orderBy('step_number')
                    ->get()
            )->resolve($request),
            'certifications' => CertificationResource::collection(
                Certification::query()
                    ->active()
                    ->forLocale($locale)
                    ->orderBy('sort_order')
                    ->get()
            )->resolve($request),
        ];
    }

    private function resolveLocale(Request $request): string
    {
        $locale = (string) ($request->query('locale') ?? $request->header('Accept-Language') ?? 'en');
        $resolved = substr($locale, 0, 2);

        return in_array($resolved, ['en', 'ko', 'zh'], true) ? $resolved : 'en';
    }
}
