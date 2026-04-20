<?php

namespace Database\Seeders;

use App\Models\SiteSection;
use Illuminate\Database\Seeder;

class SiteSectionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->localizedSections() as $section) {
            SiteSection::query()->updateOrCreate(
                [
                    'page' => $section['page'],
                    'section' => $section['section'],
                    'locale' => $section['locale'],
                ],
                $section
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function localizedSections(): array
    {
        $sections = [
            [
                'page' => 'home',
                'section' => 'hero',
                'title' => "Ocean's Legacy, Crafted with Artisan Tech.",
                'subtitle' => 'Premium tableware from recycled oyster shells.',
                'cta_label' => 'Explore Collection',
                'cta_url' => '/store',
            ],
            [
                'page' => 'home',
                'section' => 'intro',
                'title' => 'What is Shellfin?',
                'body' => 'Shellfin transforms discarded oyster shells into premium mineralware. Lighter, stronger, and safer than traditional ceramics - with zero water absorption and natural antibacterial properties.',
            ],
            [
                'page' => 'home',
                'section' => 'sustainability',
                'title' => '7 Million Tonnes of Waste, Reimagined.',
                'body' => 'The global seafood industry discards over 7 million tonnes of shell waste each year. Shellfin reclaims this resource, turning environmental burden into artisan material.',
            ],
            [
                'page' => 'material',
                'section' => 'hero',
                'title' => 'The Material',
                'subtitle' => 'Shellfin is not ceramic. It is something entirely new.',
            ],
            [
                'page' => 'material',
                'section' => 'origin',
                'title' => 'Born from the Sea',
                'body' => 'Oyster shells collected from coastal waste streams are thermally purified at 200°C-700°C, carbonising all organic matter to leave only the purest mineral essence. The result is a pelletised raw material unlike anything before it.',
            ],
            [
                'page' => 'material',
                'section' => 'properties_intro',
                'title' => 'Why Shellfin?',
                'subtitle' => 'Six reasons it outperforms traditional ceramics.',
            ],
            [
                'page' => 'material',
                'section' => 'process_intro',
                'title' => 'From Shell to Table',
                'subtitle' => 'Four steps from ocean waste to premium product.',
            ],
            [
                'page' => 'material',
                'section' => 'certifications_intro',
                'title' => 'Proven by Data',
                'subtitle' => 'Every claim backed by official test reports.',
            ],
            [
                'page' => 'b2b',
                'section' => 'hero',
                'title' => 'Partner with Shellfin',
                'subtitle' => 'Source premium shell-based pellets for your manufacturing.',
            ],
            [
                'page' => 'b2b',
                'section' => 'intro',
                'title' => 'The Raw Material Opportunity',
                'body' => 'Shellfin pellets (OX-S) are the first commercially available olefin cross-linked oyster shell compound. Lighter than ceramic, stronger than plastic, and entirely natural. Available for B2B purchase and licensing.',
            ],
            [
                'page' => 'b2b',
                'section' => 'advantages',
                'title' => 'Why Manufacturers Choose Shellfin',
                'body' => 'Our pellets integrate with standard compression moulding equipment. No new infrastructure required. Drop-in replacement for conventional ceramic or plastic compounds in tableware, wellness, and architectural applications.',
                'metadata' => [
                    'points' => [
                        'Compatible with existing compression moulding lines',
                        '35% weight reduction vs ceramic compounds',
                        '0.00% water absorption - food safe out of the mould',
                        'Natural antibacterial - no coating process required',
                        'Certified non-toxic, non-flammable',
                    ],
                ],
            ],
            [
                'page' => 'b2b',
                'section' => 'cta',
                'title' => 'Request a Sample Pack',
                'subtitle' => 'We send pellet samples and full technical specifications.',
                'cta_label' => 'Submit Inquiry',
                'cta_url' => '/b2b#inquiry',
            ],
            [
                'page' => 'community',
                'section' => 'hero',
                'title' => 'Design with Shellfin',
                'subtitle' => 'Share your ideas. Fund your vision. Build with ocean.',
            ],
            [
                'page' => 'community',
                'section' => 'intro',
                'title' => 'What could Shellfin become?',
                'body' => "Shellfin material can be moulded into almost anything. We're looking for designers, makers, and entrepreneurs who have ideas for what the next Shellfin product should be. Share your concept, gather community support, and link your funding campaign.",
            ],
        ];

        $localized = [];

        foreach ($sections as $sortOrder => $section) {
            foreach (['en', 'ko', 'zh'] as $locale) {
                $localized[] = [
                    ...$this->localizeSection($section, $locale),
                    'locale' => $locale,
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ];
            }
        }

        return $localized;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private function localizeSection(array $section, string $locale): array
    {
        if ($locale === 'en') {
            return $section;
        }

        $prefix = strtoupper($locale);
        $localized = $section;

        foreach (['title', 'subtitle', 'body', 'cta_label'] as $field) {
            if (isset($localized[$field]) && is_string($localized[$field])) {
                $localized[$field] = sprintf('[%s] %s', $prefix, $localized[$field]);
            }
        }

        if (isset($localized['metadata']['points']) && is_array($localized['metadata']['points'])) {
            $localized['metadata']['points'] = array_map(
                fn (string $point): string => sprintf('[%s] %s', $prefix, $point),
                $localized['metadata']['points']
            );
        }

        return $localized;
    }
}
