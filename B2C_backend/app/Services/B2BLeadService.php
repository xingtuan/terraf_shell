<?php

namespace App\Services;

use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use App\Mail\B2BLeadSubmittedMail;
use App\Models\B2BLead;
use App\Models\User;
use App\Services\Email\EmailDispatchService;
use App\Services\Email\EmailPayloadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class B2BLeadService
{
    public function __construct(
        private readonly GovernanceService $governanceService,
        private readonly EmailDispatchService $emailDispatchService,
        private readonly EmailPayloadFactory $emailPayloadFactory,
    ) {}

    public function createBusinessContact(array $data): B2BLead
    {
        return $this->createLead(
            B2BLeadType::BusinessContact,
            $data
        );
    }

    public function createPartnershipInquiry(array $data): B2BLead
    {
        return $this->createLead(
            B2BLeadType::from($data['collaboration_type']),
            $data,
            function (B2BLead $lead, array $payload): void {
                $lead->partnershipInquiry()->create([
                    'collaboration_type' => $payload['collaboration_type'],
                    'collaboration_goal' => $payload['collaboration_goal'],
                    'project_stage' => $payload['project_stage'] ?? null,
                    'timeline' => $payload['timeline'] ?? null,
                    'metadata' => $payload['metadata'] ?? null,
                ]);
            }
        );
    }

    public function createSampleRequest(array $data): B2BLead
    {
        return $this->createLead(
            B2BLeadType::SampleRequest,
            $data,
            function (B2BLead $lead, array $payload): void {
                $lead->sampleRequest()->create([
                    'material_interest' => $payload['material_interest'],
                    'quantity_estimate' => $payload['quantity_estimate'] ?? null,
                    'shipping_country' => $payload['shipping_country'] ?? ($payload['country'] ?? null),
                    'shipping_region' => $payload['shipping_region'] ?? ($payload['region'] ?? null),
                    'shipping_address' => $payload['shipping_address'] ?? null,
                    'intended_use' => $payload['intended_use'],
                    'metadata' => $payload['metadata'] ?? null,
                ]);
            }
        );
    }

    public function listForAdmin(array $filters): LengthAwarePaginator
    {
        $query = B2BLead::query()
            ->with(['assignee.profile', 'reviewer.profile', 'partnershipInquiry', 'sampleRequest'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $this->applyFilters($query, $filters);

        return $query
            ->paginate($this->perPage($filters['per_page'] ?? null))
            ->withQueryString();
    }

    public function updateForAdmin(B2BLead $lead, array $data, User $admin): B2BLead
    {
        return DB::transaction(function () use ($lead, $data, $admin): B2BLead {
            $changes = [];
            $nextAssignedTo = array_key_exists('assigned_to', $data)
                ? (filled($data['assigned_to']) ? (int) $data['assigned_to'] : null)
                : $lead->assigned_to;

            if (array_key_exists('status', $data) && $data['status'] !== $lead->status) {
                $changes['status'] = [
                    'from' => $lead->status,
                    'to' => $data['status'],
                ];
                $lead->status = $data['status'];
            }

            if (array_key_exists('internal_notes', $data) && $data['internal_notes'] !== $lead->internal_notes) {
                $changes['internal_notes'] = true;
                $lead->internal_notes = $data['internal_notes'];
            }

            if (array_key_exists('assigned_to', $data) && $nextAssignedTo !== $lead->assigned_to) {
                $changes['assigned_to'] = [
                    'from' => $lead->assigned_to,
                    'to' => $nextAssignedTo,
                ];
                $lead->assigned_to = $nextAssignedTo;
            }

            if ($changes === []) {
                return $this->loadLead($lead);
            }

            $lead->reviewed_by = $admin->id;
            $lead->reviewed_at = now();
            $lead->save();

            $description = match (true) {
                count($changes) > 1 => 'B2B lead review updated.',
                isset($changes['status']) => 'B2B lead status updated.',
                isset($changes['assigned_to']) => 'B2B lead owner updated.',
                default => 'B2B lead notes updated.',
            };

            $this->governanceService->recordAdminAction(
                $admin,
                'b2b_lead.updated',
                $description,
                $changes,
                $lead
            );

            $lead = $this->loadLead($lead);

            DB::afterCommit(function () use ($lead, $changes): void {
                if (isset($changes['assigned_to']) && $lead->assignee instanceof User) {
                    $this->emailDispatchService->sendEvent(
                        'lead.assigned_admin_notification',
                        $this->emailPayloadFactory->forLead($lead),
                        [
                            'to' => [$lead->assignee],
                            'related' => $lead,
                            'idempotency_key' => 'lead.assigned_admin_notification:'.$lead->id.':'.$lead->assigned_to,
                        ],
                    );
                }

                if (isset($changes['status'])) {
                    $this->emailDispatchService->sendEvent(
                        'lead.status_changed_user_update',
                        $this->emailPayloadFactory->forLead($lead),
                        [
                            'related' => $lead,
                            'idempotency_key' => 'lead.status_changed_user_update:'.$lead->id.':'.$lead->status,
                        ],
                    );
                }
            });

            return $lead;
        });
    }

    public function loadLead(B2BLead $lead): B2BLead
    {
        return $lead->load(['assignee.profile', 'reviewer.profile', 'partnershipInquiry', 'sampleRequest']);
    }

    public function exportForAdmin(array $filters): StreamedResponse
    {
        $filename = 'b2b-leads-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'reference',
                'lead_type',
                'interest_type',
                'application_type',
                'inquiry_type',
                'status',
                'name',
                'company_name',
                'organization_type',
                'email',
                'phone',
                'country',
                'region',
                'company_website',
                'job_title',
                'expected_use_case',
                'estimated_quantity',
                'timeline',
                'source_page',
                'collaboration_type',
                'material_interest',
                'created_at',
                'reviewed_at',
            ]);

            $query = B2BLead::query()
                ->with(['partnershipInquiry', 'sampleRequest'])
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            $this->applyFilters($query, $filters);

            foreach ($query->cursor() as $lead) {
                fputcsv($handle, [
                    $lead->reference ?: sprintf('INQ-%06d', $lead->id),
                    $lead->lead_type,
                    $lead->interest_type,
                    $lead->application_type,
                    $lead->inquiry_type,
                    $lead->status,
                    $lead->name,
                    $lead->company_name,
                    $lead->organization_type,
                    $lead->email,
                    $lead->phone,
                    $lead->country,
                    $lead->region,
                    $lead->company_website,
                    $lead->job_title,
                    $lead->expected_use_case,
                    $lead->estimated_quantity,
                    $lead->timeline,
                    $lead->source_page,
                    $lead->partnershipInquiry?->collaboration_type,
                    $lead->sampleRequest?->material_interest,
                    $lead->created_at?->toISOString(),
                    $lead->reviewed_at?->toISOString(),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function createLead(B2BLeadType $type, array $data, ?callable $detailCreator = null): B2BLead
    {
        $lead = DB::transaction(function () use ($type, $data, $detailCreator): B2BLead {
            $lead = B2BLead::query()->create([
                'lead_type' => $type->value,
                'interest_type' => $data['interest_type'] ?? null,
                'application_type' => $data['application_type'] ?? ($data['inquiry_type'] ?? null),
                'expected_use_case' => $data['expected_use_case'] ?? null,
                'estimated_quantity' => $data['estimated_quantity'] ?? ($data['quantity_estimate'] ?? null),
                'timeline' => $data['timeline'] ?? null,
                'name' => $data['name'],
                'company_name' => $data['company_name'],
                'organization_type' => $data['organization_type'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'country' => $data['country'] ?? null,
                'region' => $data['region'] ?? null,
                'company_website' => $data['company_website'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'inquiry_type' => $data['inquiry_type'] ?? $data['application_type'] ?? $type->label(),
                'message' => $data['message'],
                'source_page' => $data['source_page'] ?? null,
                'status' => B2BLeadStatus::New->value,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $lead->forceFill([
                'reference' => sprintf('INQ-%06d', $lead->id),
            ])->save();

            if ($detailCreator !== null) {
                $detailCreator($lead, $data);
            }

            $lead = $this->loadLead($lead);

            DB::afterCommit(fn () => $this->sendLeadEmails($lead));

            return $lead;
        });

        return $lead;
    }

    private function sendLeadEmails(B2BLead $lead): void
    {
        $payload = $this->emailPayloadFactory->forLead($lead);

        $userEvent = match ($lead->lead_type) {
            B2BLeadType::BusinessContact->value => 'inquiry.submitted_user_confirmation',
            B2BLeadType::SampleRequest->value => 'sample_request.submitted_user_confirmation',
            default => 'partnership_inquiry.submitted_user_confirmation',
        };

        $adminEvent = $lead->lead_type === B2BLeadType::BusinessContact->value
            ? 'inquiry.submitted_admin_notification'
            : 'b2b_lead.submitted_admin_notification';

        $this->emailDispatchService->sendEvent($userEvent, $payload, [
            'related' => $lead,
            'idempotency_key' => $userEvent.':'.$lead->id,
        ]);

        $adminLog = $this->emailDispatchService->sendEvent($adminEvent, $payload, [
            'related' => $lead,
            'idempotency_key' => $adminEvent.':'.$lead->id,
        ]);

        if ($adminLog?->status === 'skipped') {
            $this->sendAdminNotification($lead);
        }
    }

    private function sendAdminNotification(B2BLead $lead): void
    {
        if (! (bool) config('community.b2b_leads.notify_admins', false)) {
            return;
        }

        $recipients = collect(config('community.b2b_leads.notification_recipients', []))
            ->map(fn ($email): string => trim((string) $email))
            ->filter()
            ->values();

        if ($recipients->isEmpty()) {
            $recipients = User::query()
                ->where('role', 'admin')
                ->where('account_status', 'active')
                ->pluck('email')
                ->filter()
                ->values();
        }

        if ($recipients->isEmpty()) {
            return;
        }

        Mail::to($recipients->all())->send(new B2BLeadSubmittedMail($lead));
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);

            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('reference', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('company_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('message', 'like', '%'.$search.'%')
                    ->orWhere('inquiry_type', 'like', '%'.$search.'%')
                    ->orWhere('application_type', 'like', '%'.$search.'%')
                    ->orWhere('interest_type', 'like', '%'.$search.'%');
            });
        }

        if (! empty($filters['lead_type'])) {
            $query->where('lead_type', $filters['lead_type']);
        }

        if (! empty($filters['interest_type'])) {
            $query->where('interest_type', $filters['interest_type']);
        }

        if (! empty($filters['application_type'])) {
            $query->where('application_type', 'like', '%'.$filters['application_type'].'%');
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('assigned_to', $filters) && filled($filters['assigned_to'])) {
            $query->where('assigned_to', (int) $filters['assigned_to']);
        }

        if (! empty($filters['country'])) {
            $query->where('country', 'like', '%'.$filters['country'].'%');
        }

        if (! empty($filters['company_name'])) {
            $query->where('company_name', 'like', '%'.$filters['company_name'].'%');
        }

        if (! empty($filters['organization_type'])) {
            $query->where('organization_type', 'like', '%'.$filters['organization_type'].'%');
        }

        if (! empty($filters['source_page'])) {
            $query->where('source_page', 'like', '%'.$filters['source_page'].'%');
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_until'])) {
            $query->whereDate('created_at', '<=', $filters['created_until']);
        }
    }

    private function perPage(null|int|string $requested): int
    {
        $default = (int) config('community.pagination.default_per_page', 20);
        $max = (int) config('community.pagination.max_per_page', 50);
        $value = (int) ($requested ?: $default);

        return max(1, min($value, $max));
    }
}
