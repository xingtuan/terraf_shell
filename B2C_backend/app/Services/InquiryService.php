<?php

namespace App\Services;

use App\Models\Inquiry;

class InquiryService
{
    public function create(array $data): Inquiry
    {
        return Inquiry::query()->create([
            'name' => $data['name'],
            'company_name' => $data['company_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'country' => $data['country'] ?? null,
            'inquiry_type' => $data['inquiry_type'],
            'message' => $data['message'],
            'source_page' => $data['source_page'] ?? null,
            'status' => 'new',
        ]);
    }
}
