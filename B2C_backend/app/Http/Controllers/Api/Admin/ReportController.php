<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateReportRequest;
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
            __('api.moderation.report_status_updated')
        );
    }

    public function review(
        ModerateReportRequest $request,
        Report $report,
        AdminModerationService $moderationService
    ): JsonResponse {
        $report = $moderationService->markReportReviewed(
            $report,
            $request->user(),
            $request->internalNote(),
            $request->publicNote()
        );

        return $this->successResponse(new ReportResource($report), __('api.moderation.report_reviewed'));
    }

    public function dismiss(
        ModerateReportRequest $request,
        Report $report,
        AdminModerationService $moderationService
    ): JsonResponse {
        $report = $moderationService->dismissReport(
            $report,
            $request->user(),
            $request->internalNote(),
            $request->publicNote()
        );

        return $this->successResponse(new ReportResource($report), __('api.moderation.report_dismissed'));
    }

    public function resolve(
        ModerateReportRequest $request,
        Report $report,
        AdminModerationService $moderationService
    ): JsonResponse {
        $report = $moderationService->resolveReport(
            $report,
            $request->user(),
            $request->validated('resolution_action'),
            $request->internalNote(),
            $request->publicNote()
        );

        return $this->successResponse(new ReportResource($report), __('api.moderation.report_resolved'));
    }

    public function hideTarget(
        ModerateReportRequest $request,
        Report $report,
        AdminModerationService $moderationService
    ): JsonResponse {
        $report = $moderationService->resolveReportAndHideTarget(
            $report,
            $request->user(),
            $request->internalNote(),
            $request->publicNote()
        );

        return $this->successResponse(new ReportResource($report), __('api.moderation.report_resolved_hidden'));
    }

    public function rejectTarget(
        ModerateReportRequest $request,
        Report $report,
        AdminModerationService $moderationService
    ): JsonResponse {
        $report = $moderationService->resolveReportAndRejectTarget(
            $report,
            $request->user(),
            $request->internalNote(),
            $request->publicNote()
        );

        return $this->successResponse(new ReportResource($report), __('api.moderation.report_resolved_rejected'));
    }

    public function warnUser(
        ModerateReportRequest $request,
        Report $report,
        AdminModerationService $moderationService
    ): JsonResponse {
        $report = $moderationService->resolveReportAndWarnUser(
            $report,
            $request->user(),
            $request->internalNote(),
            $request->publicNote()
        );

        return $this->successResponse(new ReportResource($report), __('api.moderation.report_resolved_warned'));
    }

    public function restrictUser(
        ModerateReportRequest $request,
        Report $report,
        AdminModerationService $moderationService
    ): JsonResponse {
        $report = $moderationService->resolveReportAndRestrictUser(
            $report,
            $request->user(),
            $request->internalNote(),
            $request->publicNote()
        );

        return $this->successResponse(new ReportResource($report), __('api.moderation.report_resolved_restricted'));
    }

    public function banUser(
        ModerateReportRequest $request,
        Report $report,
        AdminModerationService $moderationService
    ): JsonResponse {
        $report = $moderationService->resolveReportAndBanUser(
            $report,
            $request->user(),
            $request->internalNote(),
            $request->publicNote()
        );

        return $this->successResponse(new ReportResource($report), __('api.moderation.report_resolved_banned'));
    }
}
