<?php

namespace App\Models;

use Database\Factories\SampleRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SampleRequest extends Model
{
    /** @use HasFactory<SampleRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'material_interest',
        'quantity_estimate',
        'shipping_country',
        'shipping_region',
        'shipping_address',
        'intended_use',
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
