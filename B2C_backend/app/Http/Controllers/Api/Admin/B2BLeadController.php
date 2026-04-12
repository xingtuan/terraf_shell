<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListB2BLeadsRequest;
use App\Http\Requests\Admin\UpdateB2BLeadRequest;
use App\Http\Resources\B2BLeadResource;
use App\Models\B2BLead;
use App\Services\B2BLeadService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class B2BLeadController extends Controller
{
    public function index(ListB2BLeadsRequest $request, B2BLeadService $b2BLeadService): JsonResponse
    {
        $leads = $b2BLeadService->listForAdmin($request->validated());

        return $this->paginatedResponse(
            $leads,
            B2BLeadResource::collection($leads->getCollection())
        );
    }

    public function show(B2BLead $b2bLead, B2BLeadService $b2BLeadService): JsonResponse
    {
        return $this->successResponse(new B2BLeadResource($b2BLeadService->loadLead($b2bLead)));
    }

    public function update(
        UpdateB2BLeadRequest $request,
        B2BLead $b2bLead,
        B2BLeadService $b2BLeadService
    ): JsonResponse {
        $lead = $b2BLeadService->updateForAdmin($b2bLead, $request->validated(), $request->user());

        return $this->successResponse(
            new B2BLeadResource($lead),
            'B2B lead updated successfully.'
        );
    }

    public function export(ListB2BLeadsRequest $request, B2BLeadService $b2BLeadService): StreamedResponse
    {
        return $b2BLeadService->exportForAdmin($request->validated());
    }
}
