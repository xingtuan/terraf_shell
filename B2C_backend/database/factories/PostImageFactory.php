<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostImage>
 */
class PostImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $file = fake()->uuid().'.jpg';

        return [
            'post_id' => Post::factory(),
            'path' => 'posts/'.$file,
            'url' => 'https://cdn.example.com/posts/'.$file,
            'alt_text' => fake()->sentence(6),
            'sort_order' => fake()->numberBetween(0, 3),
        ];
    }
}
