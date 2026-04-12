<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request, ReportService $reportService): JsonResponse
    {
        $report = $reportService->create($request->user(), $request->validated());

        return $this->successResponse(
            new ReportResource($report),
            'Report submitted successfully.',
            201
        );
    }
}
