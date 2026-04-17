<?php

namespace App\Models;

use App\Enums\B2BLeadType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Inquiry extends B2BLead
{
    protected static function booted(): void
    {
        static::addGlobalScope('business_contact_enquiries', function (Builder $query): void {
            $query->where('lead_type', B2BLeadType::BusinessContact->value);
        });
    }

    public function getSubjectAttribute(): string
    {
        $metadataSubject = data_get($this->metadata, 'subject');
        $application = data_get($this->metadata, 'application');

        if (filled($metadataSubject)) {
            return Str::limit(Str::squish((string) $metadataSubject), 120);
        }

        if (filled($application)) {
            return Str::limit(Str::squish((string) $application), 120);
        }

        if (filled($this->inquiry_type) && $this->inquiry_type !== B2BLeadType::BusinessContact->label()) {
            return Str::limit(Str::squish((string) $this->inquiry_type), 120);
        }

        return Str::limit(Str::squish((string) $this->message), 120);
    }
}
