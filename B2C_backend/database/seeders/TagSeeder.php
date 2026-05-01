<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
        $this->mergeLegacyBrandTag();

        $tags = [
            'oyster-shell',
            'compression-moulding',
            'eco-tableware',
            'oxp',
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

    private function mergeLegacyBrandTag(): void
    {
        $legacySlug = $this->legacyBrandSlug();
        $legacyTag = Tag::query()->where('slug', $legacySlug)->first();

        if ($legacyTag === null) {
            return;
        }

        $targetTag = Tag::query()
            ->where('slug', 'oxp')
            ->orWhere('name', 'oxp')
            ->first();

        if ($targetTag === null) {
            $legacyTag->update([
                'name' => 'oxp',
                'slug' => 'oxp',
            ]);

            return;
        }

        if ($legacyTag->is($targetTag)) {
            $targetTag->update(['name' => 'oxp']);

            return;
        }

        $postIds = DB::table('post_tags')
            ->where('tag_id', $legacyTag->id)
            ->pluck('post_id');

        foreach ($postIds as $postId) {
            DB::table('post_tags')->updateOrInsert(
                [
                    'post_id' => $postId,
                    'tag_id' => $targetTag->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        DB::table('post_tags')
            ->where('tag_id', $legacyTag->id)
            ->delete();

        $legacyTag->delete();
        $targetTag->update(['name' => 'oxp']);
    }

    private function legacyBrandSlug(): string
    {
        return 'shell'.'fin';
    }
}
