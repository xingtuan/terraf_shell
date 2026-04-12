<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inquiry\StoreInquiryRequest;
use App\Http\Resources\InquiryResource;
use App\Services\InquiryService;
use Illuminate\Http\JsonResponse;

class InquiryController extends Controller
{
    public function store(StoreInquiryRequest $request, InquiryService $inquiryService): JsonResponse
    {
        $inquiry = $inquiryService->create($request->validated());

        return $this->successResponse(
            new InquiryResource($inquiry),
            'Inquiry submitted successfully.',
            201
        );
    }
}
