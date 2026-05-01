<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MaterialController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->successResponse($this->payload());
    }

    public function show(string $identifier): JsonResponse
    {
        return $this->successResponse($this->payload());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'name' => 'OXP',
            'tagline' => "Ocean's Legacy, Crafted with Artisan Tech.",
            'origin' => 'Recycled oyster shells collected from coastal waste streams',
            'process_steps' => [
                [
                    'step' => 1,
                    'title' => 'Collection',
                    'body' => 'Discarded oyster shells gathered from seafood industry waste',
                ],
                [
                    'step' => 2,
                    'title' => 'Thermal Purification',
                    'body' => 'Shells treated between 200C-700C, carbonising organic matter',
                ],
                [
                    'step' => 3,
                    'title' => 'Pelletisation',
                    'body' => 'Purified shell material formed into uniform pellets',
                ],
                [
                    'step' => 4,
                    'title' => 'Compression Moulding',
                    'body' => 'Pellets compressed under high heat into final product form',
                ],
            ],
            'properties' => [
                [
                    'key' => 'weight',
                    'label' => 'Lightweight',
                    'value' => '1.5-1.6 specific gravity',
                    'vs' => '35% lighter than ceramic (2.4)',
                ],
                [
                    'key' => 'strength',
                    'label' => 'Impact Resistant',
                    'value' => 'Unbreakable integrity',
                    'vs' => 'Overcomes ceramic chipping & cracking',
                ],
                [
                    'key' => 'absorption',
                    'label' => 'Zero Absorption',
                    'value' => '0.00% water absorption',
                    'vs' => 'No odour, no staining, no bacteria',
                ],
                [
                    'key' => 'antibacterial',
                    'label' => 'Natural Antibacterial',
                    'value' => 'Weak alkaline inhibition',
                    'vs' => 'No artificial coatings needed',
                ],
                [
                    'key' => 'grip',
                    'label' => 'Mineral Grip',
                    'value' => 'Fine mineral texture surface',
                    'vs' => 'Non-slip even when wet with soap',
                ],
                [
                    'key' => 'otr',
                    'label' => 'Selective Flow',
                    'value' => 'OTR 500 cc/m2/day',
                    'vs' => 'Breathable yet moisture-blocking',
                ],
            ],
            'certifications' => [
                [
                    'key' => 'absorption',
                    'label' => 'Water Absorption Test',
                    'value' => '0.00%',
                ],
                [
                    'key' => 'toxicity',
                    'label' => 'Toxicity Test',
                    'value' => 'Zero heavy metals, zero microplastics',
                ],
                [
                    'key' => 'acid',
                    'label' => 'Acid/Corrosion Resistance',
                    'value' => 'No surface degradation',
                ],
                [
                    'key' => 'fire',
                    'label' => 'Non-Toxic Fireproof',
                    'value' => 'Non-flammable, zero toxic gas',
                ],
                [
                    'key' => 'otr',
                    'label' => 'OTR Data',
                    'value' => '500 cc/m2/day certified',
                ],
            ],
            'models' => [
                [
                    'id' => 'lite_15',
                    'name' => '1.5 Lite',
                    'finish' => 'Glossy',
                    'gravity' => 1.5,
                    'description' => 'Smooth brilliance, light daily life',
                ],
                [
                    'id' => 'heritage_16',
                    'name' => '1.6 Heritage',
                    'finish' => 'Matte',
                    'gravity' => 1.6,
                    'description' => 'Deep matte texture, stable tranquility',
                ],
            ],
            'colors' => [
                [
                    'id' => 'ocean_bone',
                    'temp' => '200C',
                    'name' => 'Ocean Bone',
                    'description' => 'Warm white, the inherent purity of seashells',
                ],
                [
                    'id' => 'forged_ash',
                    'temp' => '700C',
                    'name' => 'Forged Ash',
                    'description' => 'Serene grey, all organic matter emptied by heat',
                ],
            ],
        ];
    }
}
