<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreSampleRequestRequest;
use App\Http\Resources\B2BLeadResource;
use App\Services\B2BLeadService;
use Illuminate\Http\JsonResponse;

class SampleRequestController extends Controller
{
    public function store(StoreSampleRequestRequest $request, B2BLeadService $b2BLeadService): JsonResponse
    {
        $lead = $b2BLeadService->createSampleRequest($request->validated());

        return $this->successResponse(
            new B2BLeadResource($lead),
            'Sample request submitted successfully.',
            201
        );
    }
}
