<?php

namespace App\Models;

use Database\Factories\PartnershipInquiryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnershipInquiry extends Model
{
    /** @use HasFactory<PartnershipInquiryFactory> */
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'collaboration_type',
        'collaboration_goal',
        'project_stage',
        'timeline',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(B2BLead::class, 'lead_id');
    }
}
