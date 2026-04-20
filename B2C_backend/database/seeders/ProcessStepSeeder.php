<?php

namespace Database\Seeders;

use App\Models\ProcessStep;
use Illuminate\Database\Seeder;

class ProcessStepSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->localizedSteps() as $step) {
            ProcessStep::query()->updateOrCreate(
                [
                    'step_number' => $step['step_number'],
                    'locale' => $step['locale'],
                ],
                $step
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function localizedSteps(): array
    {
        $steps = [
            [
                'step_number' => 1,
                'title' => 'Collection',
                'body' => 'Discarded oyster shells are gathered from seafood industry waste streams and sorted for material recovery.',
                'icon' => 'shell',
            ],
            [
                'step_number' => 2,
                'title' => 'Thermal Purification',
                'body' => 'Shells are treated between 200°C-700°C, carbonising organic matter and isolating the pure mineral base.',
                'icon' => 'flame',
            ],
            [
                'step_number' => 3,
                'title' => 'Pelletisation',
                'body' => 'Purified shell minerals are refined into consistent Shellfin pellets for scalable production and repeatable quality.',
                'icon' => 'circle-dot',
            ],
            [
                'step_number' => 4,
                'title' => 'Compression Moulding',
                'body' => 'The pellets are compression-moulded into lightweight, high-impact products for tableware and broader design applications.',
                'icon' => 'square-stack',
            ],
        ];

        $localized = [];

        foreach ($steps as $step) {
            foreach (['en', 'ko', 'zh'] as $locale) {
                $localized[] = [
                    ...$this->localize($step, $locale),
                    'locale' => $locale,
                    'is_active' => true,
                ];
            }
        }

        return $localized;
    }

    /**
     * @param  array<string, mixed>  $step
     * @return array<string, mixed>
     */
    private function localize(array $step, string $locale): array
    {
        if ($locale === 'en') {
            return $step;
        }

        $prefix = strtoupper($locale);

        foreach (['title', 'body'] as $field) {
            if (isset($step[$field]) && is_string($step[$field])) {
                $step[$field] = sprintf('[%s] %s', $prefix, $step[$field]);
            }
        }

        return $step;
    }
}
