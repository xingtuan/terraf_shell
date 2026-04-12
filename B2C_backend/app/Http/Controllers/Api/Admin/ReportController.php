<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateReportStatusRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\AdminModerationService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reportService): JsonResponse
    {
        abort_unless($request->user()?->canModerate(), 403);

        $reports = $reportService->listForAdmin($request->only(['status', 'per_page']));

        return $this->paginatedResponse(
            $reports,
            ReportResource::collection($reports->getCollection())
        );
    }

    public function updateStatus(
        UpdateReportStatusRequest $request,
        Report $report,
        AdminModerationService $moderationService
    ): JsonResponse {
        $report = $moderationService->updateReportStatus(
            $report,
            $request->validated()['status'],
            $request->user(),
            $request->validated()['reason'] ?? null
        );

        return $this->successResponse(
            new ReportResource($report),
            'Report status updated successfully.'
        );
    }
}
