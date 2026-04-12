<?php

namespace App\Services;

use App\Models\Inquiry;

class InquiryService
{
    public function __construct(
        private readonly B2BLeadService $b2BLeadService,
    ) {}

    public function create(array $data): Inquiry
    {
        return Inquiry::query()->findOrFail(
            $this->b2BLeadService->createBusinessContact($data)->id
        );
    }
}
