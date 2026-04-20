<?php

namespace Database\Seeders;

use App\Models\MaterialProperty;
use Illuminate\Database\Seeder;

class MaterialPropertySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->localizedProperties() as $property) {
            MaterialProperty::query()->updateOrCreate(
                [
                    'key' => $property['key'],
                    'locale' => $property['locale'],
                ],
                $property
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function localizedProperties(): array
    {
        $properties = [
            [
                'key' => 'weight',
                'label' => 'Lightweight',
                'value' => '1.5-1.6 specific gravity',
                'comparison' => '35% lighter than ceramic (2.4)',
                'icon' => 'feather',
            ],
            [
                'key' => 'strength',
                'label' => 'Impact Resistant',
                'value' => 'Unbreakable integrity',
                'comparison' => 'Overcomes ceramic chipping & cracking',
                'icon' => 'shield',
            ],
            [
                'key' => 'absorption',
                'label' => 'Zero Absorption',
                'value' => '0.00% water absorption',
                'comparison' => 'No odour, no staining, no bacteria',
                'icon' => 'droplets',
            ],
            [
                'key' => 'antibacterial',
                'label' => 'Natural Antibacterial',
                'value' => 'Weak alkaline inhibition',
                'comparison' => 'No artificial coatings needed',
                'icon' => 'leaf',
            ],
            [
                'key' => 'grip',
                'label' => 'Mineral Grip',
                'value' => 'Fine mineral texture surface',
                'comparison' => 'Non-slip even when wet with soap',
                'icon' => 'hand',
            ],
            [
                'key' => 'otr',
                'label' => 'Selective Flow',
                'value' => 'OTR 500 cc/m2/day',
                'comparison' => 'Breathable yet moisture-blocking',
                'icon' => 'wind',
            ],
        ];

        $localized = [];

        foreach ($properties as $sortOrder => $property) {
            foreach (['en', 'ko', 'zh'] as $locale) {
                $localized[] = [
                    ...$this->localize($property, $locale),
                    'locale' => $locale,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ];
            }
        }

        return $localized;
    }

    /**
     * @param  array<string, mixed>  $property
     * @return array<string, mixed>
     */
    private function localize(array $property, string $locale): array
    {
        if ($locale === 'en') {
            return $property;
        }

        $prefix = strtoupper($locale);

        foreach (['label', 'value', 'comparison'] as $field) {
            if (isset($property[$field]) && is_string($property[$field])) {
                $property[$field] = sprintf('[%s] %s', $prefix, $property[$field]);
            }
        }

        return $property;
    }
}
