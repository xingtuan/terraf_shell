<?php

namespace Database\Seeders;

use App\Models\Certification;
use Illuminate\Database\Seeder;

class CertificationSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->localizedCertifications() as $certification) {
            Certification::query()->updateOrCreate(
                [
                    'key' => $certification['key'],
                    'locale' => $certification['locale'],
                ],
                $certification
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function localizedCertifications(): array
    {
        $certifications = [
            [
                'key' => 'absorption',
                'label' => 'Water Absorption Test',
                'value' => '0.00%',
                'description' => 'Validated as a zero-absorption material body.',
                'badge_color' => '#2D6A4F',
            ],
            [
                'key' => 'toxicity',
                'label' => 'Toxicity Test',
                'value' => 'Zero heavy metals, zero microplastics',
                'description' => 'Prepared for food-contact and interior safety review.',
                'badge_color' => '#1D3557',
            ],
            [
                'key' => 'acid',
                'label' => 'Acid/Corrosion Resistance',
                'value' => 'No surface degradation',
                'description' => 'Supports food-service and daily-use environments that require chemical stability.',
                'badge_color' => '#BC6C25',
            ],
            [
                'key' => 'fire',
                'label' => 'Non-Toxic Fireproof',
                'value' => 'Non-flammable, zero toxic gas',
                'description' => 'Suitable for review in safe, fire-resistant applications.',
                'badge_color' => '#9C6644',
            ],
            [
                'key' => 'otr',
                'label' => 'OTR Data',
                'value' => '500 cc/m2/day certified',
                'description' => 'Certified oxygen transmission data supports breathable yet moisture-blocking performance.',
                'badge_color' => '#436C6D',
            ],
        ];

        $localized = [];

        foreach ($certifications as $sortOrder => $certification) {
            foreach (['en', 'ko', 'zh'] as $locale) {
                $localized[] = [
                    ...$this->localize($certification, $locale),
                    'locale' => $locale,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ];
            }
        }

        return $localized;
    }

    /**
     * @param  array<string, mixed>  $certification
     * @return array<string, mixed>
     */
    private function localize(array $certification, string $locale): array
    {
        if ($locale === 'en') {
            return $certification;
        }

        $prefix = strtoupper($locale);

        foreach (['label', 'value', 'description'] as $field) {
            if (isset($certification[$field]) && is_string($certification[$field])) {
                $certification[$field] = sprintf('[%s] %s', $prefix, $certification[$field]);
            }
        }

        return $certification;
    }
}
