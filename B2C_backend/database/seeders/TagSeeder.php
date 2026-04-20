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
        $obsoleteSeedSlugs = [
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

        Tag::query()->whereIn('slug', $obsoleteSeedSlugs)->delete();

        $tags = [
            'oyster-shell',
            'compression-moulding',
            'eco-tableware',
            'shellfin',
            'lightweight',
            'food-safe',
        ];

        foreach ($tags as $tag) {
            Tag::query()->updateOrCreate(
                ['slug' => Str::slug($tag)],
                ['name' => $tag]
            );
        }
    }
}
