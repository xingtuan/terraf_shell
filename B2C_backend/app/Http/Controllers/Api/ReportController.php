<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ListReportsRequest;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(ListReportsRequest $request, ReportService $reportService): JsonResponse
    {
        $reports = $reportService->listForReporter($request->user(), $request->validated());

        return $this->paginatedResponse(
            $reports,
            ReportResource::collection($reports->getCollection())
        );
    }

    public function show(Request $request, Report $report): JsonResponse
    {
        $this->authorize('view', $report);

        return $this->successResponse(
            new ReportResource($report->load(['reporter.profile', 'reviewer.profile', 'target']))
        );
    }

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
