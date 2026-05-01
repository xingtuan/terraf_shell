<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $obsoleteSeedSlugs = [
            'software-tools',
            'hardware',
            'productivity',
            'design',
            'ai-products',
        ];

        Category::query()->whereIn('slug', $obsoleteSeedSlugs)->delete();

        $categories = collect([
            [
                'name' => 'Tableware Design Ideas',
                'description' => 'Concept sketches, plating ideas, and form studies for OXP tableware.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Material & Craft',
                'description' => 'Material process notes, shell texture references, and compression-moulding discussions.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Sustainable Living',
                'description' => 'Daily-use ideas and circular living stories built around OXP products.',
                'sort_order' => 3,
            ],
            [
                'name' => 'B2B Partnership',
                'description' => 'Hospitality, retail, manufacturing, and collaboration opportunities with OXP.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Product Feedback',
                'description' => 'Reviews, testing notes, and feedback on OXP prototypes and finished products.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Design Challenges',
                'description' => 'Open briefs and community prompts for new OXP applications and concepts.',
                'sort_order' => 6,
            ],
        ]);

        $categories->each(function (array $category): void {
            $name = $category['name'];

            Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $category['description'],
                    'is_active' => true,
                    'sort_order' => $category['sort_order'],
                ]
            );
        });
    }
}
