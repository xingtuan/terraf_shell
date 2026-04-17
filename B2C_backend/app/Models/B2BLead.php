<?php

namespace App\Models;

use Database\Factories\B2BLeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class B2BLead extends Model
{
    /** @use HasFactory<B2BLeadFactory> */
    use HasFactory;

    protected $table = 'inquiries';

    protected $fillable = [
        'reference',
        'lead_type',
        'name',
        'company_name',
        'organization_type',
        'email',
        'phone',
        'country',
        'region',
        'company_website',
        'job_title',
        'inquiry_type',
        'message',
        'source_page',
        'status',
        'internal_notes',
        'assigned_to',
        'reviewed_by',
        'reviewed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function partnershipInquiry(): HasOne
    {
        return $this->hasOne(PartnershipInquiry::class, 'lead_id');
    }

    public function sampleRequest(): HasOne
    {
        return $this->hasOne(SampleRequest::class, 'lead_id');
    }
}
