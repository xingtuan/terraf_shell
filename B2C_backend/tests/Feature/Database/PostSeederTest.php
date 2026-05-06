<?php

namespace Tests\Feature\Database;

use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PostSeeder;
use Database\Seeders\TagSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_seeder_uses_existing_categories_without_creating_factory_categories(): void
    {
        $this->seed([
            CategorySeeder::class,
            TagSeeder::class,
            UserSeeder::class,
        ]);

        $categoryCount = Category::query()->count();

        $this->seed(PostSeeder::class);
        $this->seed(PostSeeder::class);

        $this->assertSame($categoryCount, Category::query()->count());
    }
}
