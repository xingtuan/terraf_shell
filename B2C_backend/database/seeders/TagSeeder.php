<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            'saas',
            'mobile',
            'webapp',
            'developer-tools',
            'automation',
            'productivity',
            'design-system',
            'ai',
            'community',
            'growth',
        ];

        foreach ($tags as $tag) {
            Tag::query()->updateOrCreate(
                ['slug' => Str::slug($tag)],
                ['name' => $tag]
            );
        }
    }
}
