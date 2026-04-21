<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            'shellfin',
            'oyster-shell',
            'bioplastic',
            'eco-design',
            'zero-waste',
            'tableware',
            'packaging',
            'concept-art',
            'prototype',
            'funding',
            'b2b',
            'raw-material',
            'terrafin',
            'sustainable',
            'upcycling',
        ];

        Tag::query()
            ->whereNotIn('slug', $tags)
            ->delete();

        foreach ($tags as $tag) {
            Tag::query()->updateOrCreate(
                ['slug' => $tag],
                [
                    'name' => $tag,
                    'name_ko' => null,
                    'name_zh' => null,
                ]
            );
        }
    }
}
