<?php

namespace App\Enums;

enum B2BLeadType: string
{
    case BusinessContact = 'business_contact';
    case PartnershipInquiry = 'partnership_inquiry';
    case SampleRequest = 'sample_request';
    case UniversityCollaboration = 'university_collaboration';
    case ProductDevelopmentCollaboration = 'product_development_collaboration';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }

    public static function collaborationValues(): array
    {
        return [
            self::PartnershipInquiry->value,
            self::UniversityCollaboration->value,
            self::ProductDevelopmentCollaboration->value,
        ];
    }

    public function label(): string
    {
        return __("admin.leads.type.{$this->value}");
    }
}
