<?php

namespace App\Enums;

enum B2BInterestType: string
{
    case SampleRequest = 'sample_request';
    case PelletSupply = 'pellet_supply';
    case ProductDevelopment = 'product_development';
    case BulkOrder = 'bulk_order';
    case Partnership = 'partnership';
    case Other = 'other';

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

    public function label(): string
    {
        return match ($this) {
            self::SampleRequest => 'Sample request',
            self::PelletSupply => 'Pellet supply',
            self::ProductDevelopment => 'Product development',
            self::BulkOrder => 'Bulk order',
            self::Partnership => 'Partnership',
            self::Other => 'Other',
        };
    }
}
