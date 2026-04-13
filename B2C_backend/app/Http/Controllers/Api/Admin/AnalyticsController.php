<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnalyticsOverviewRequest;
use App\Http\Resources\AnalyticsOverviewResource;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function overview(
        AnalyticsOverviewRequest $request,
        AnalyticsService $analyticsService
    ): JsonResponse {
        $overview = $analyticsService->overview(
            (int) ($request->validated()['limit'] ?? 5)
        );

        return $this->successResponse(
            new AnalyticsOverviewResource($overview),
            'Analytics overview retrieved successfully.'
        );
    }
}
