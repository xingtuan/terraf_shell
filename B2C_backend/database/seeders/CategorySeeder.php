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
        $categories = [
            'Software Tools' => 'Apps, utilities, and developer software.',
            'Hardware' => 'Devices, accessories, and desk gear.',
            'Productivity' => 'Workflows, habits, and systems that save time.',
            'Design' => 'Visual inspiration, UI patterns, and product design.',
            'AI Products' => 'AI-powered tools, assistants, and experiments.',
        ];

        foreach ($categories as $name => $description) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $description,
                    'is_active' => true,
                ]
            );
        }
    }
}
