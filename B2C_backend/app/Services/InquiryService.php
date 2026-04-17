<?php

namespace App\Services;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InquiryService
{
    public function __construct(
        private readonly B2BLeadService $b2BLeadService,
        private readonly GovernanceService $governanceService,
    ) {}

    public function create(array $data): Inquiry
    {
        return Inquiry::query()->findOrFail(
            $this->b2BLeadService->createBusinessContact($data)->id
        );
    }

    public function loadForAdmin(Inquiry $inquiry): Inquiry
    {
        return $inquiry->load(['reviewer.profile', 'assignee.profile']);
    }

    public function updateForAdmin(Inquiry $inquiry, array $data, User $admin): Inquiry
    {
        return DB::transaction(function () use ($inquiry, $data, $admin): Inquiry {
            $changes = [];
            $nextAssignedTo = array_key_exists('assigned_to', $data)
                ? (filled($data['assigned_to']) ? (int) $data['assigned_to'] : null)
                : $inquiry->assigned_to;

            if (array_key_exists('status', $data) && $data['status'] !== $inquiry->status) {
                $changes['status'] = [
                    'from' => $inquiry->status,
                    'to' => $data['status'],
                ];
                $inquiry->status = $data['status'];
            }

            if (array_key_exists('internal_notes', $data) && $data['internal_notes'] !== $inquiry->internal_notes) {
                $changes['internal_notes'] = true;
                $inquiry->internal_notes = $data['internal_notes'];
            }

            if (array_key_exists('assigned_to', $data) && $nextAssignedTo !== $inquiry->assigned_to) {
                $changes['assigned_to'] = [
                    'from' => $inquiry->assigned_to,
                    'to' => $nextAssignedTo,
                ];
                $inquiry->assigned_to = $nextAssignedTo;
            }

            if ($changes === []) {
                return $this->loadForAdmin($inquiry);
            }

            $inquiry->reviewed_by = $admin->id;
            $inquiry->reviewed_at = now();
            $inquiry->save();

            $description = match (true) {
                isset($changes['status']) => 'Enquiry status updated.',
                isset($changes['assigned_to']) => 'Enquiry assignee updated.',
                default => 'Enquiry notes updated.',
            };

            $this->governanceService->recordAdminAction(
                $admin,
                'inquiry.updated',
                $description,
                $changes,
                $inquiry
            );

            return $this->loadForAdmin($inquiry);
        });
    }
}
