<?php

namespace App\Http\Controllers\Api;

use App\Enums\B2BLeadType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreCollaborationRequest;
use App\Http\Requests\Lead\StorePartnershipInquiryRequest;
use App\Http\Resources\B2BLeadResource;
use App\Services\B2BLeadService;
use Illuminate\Http\JsonResponse;

class PartnershipInquiryController extends Controller
{
    public function store(StorePartnershipInquiryRequest $request, B2BLeadService $b2BLeadService): JsonResponse
    {
        $lead = $b2BLeadService->createPartnershipInquiry($request->validated());

        return $this->successResponse(
            new B2BLeadResource($lead),
            'Partnership inquiry submitted successfully.',
            201
        );
    }

    public function storeUniversity(
        StoreCollaborationRequest $request,
        B2BLeadService $b2BLeadService
    ): JsonResponse {
        $payload = array_merge($request->validated(), [
            'collaboration_type' => B2BLeadType::UniversityCollaboration->value,
        ]);
        $lead = $b2BLeadService->createPartnershipInquiry($payload);

        return $this->successResponse(
            new B2BLeadResource($lead),
            'University collaboration request submitted successfully.',
            201
        );
    }

    public function storeProductDevelopment(
        StoreCollaborationRequest $request,
        B2BLeadService $b2BLeadService
    ): JsonResponse {
        $payload = array_merge($request->validated(), [
            'collaboration_type' => B2BLeadType::ProductDevelopmentCollaboration->value,
        ]);
        $lead = $b2BLeadService->createPartnershipInquiry($payload);

        return $this->successResponse(
            new B2BLeadResource($lead),
            'Product development collaboration request submitted successfully.',
            201
        );
    }
}
