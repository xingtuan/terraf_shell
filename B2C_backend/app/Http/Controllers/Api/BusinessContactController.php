<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreBusinessContactRequest;
use App\Http\Resources\B2BLeadResource;
use App\Services\B2BLeadService;
use Illuminate\Http\JsonResponse;

class BusinessContactController extends Controller
{
    public function store(StoreBusinessContactRequest $request, B2BLeadService $b2BLeadService): JsonResponse
    {
        $lead = $b2BLeadService->createBusinessContact($request->validated());

        return $this->successResponse(
            new B2BLeadResource($lead),
            'Business contact submitted successfully.',
            201
        );
    }
}
