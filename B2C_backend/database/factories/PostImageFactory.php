<?php

namespace Database\Factories;

use App\Enums\IdeaMediaKind;
use App\Enums\IdeaMediaSourceType;
use App\Enums\IdeaMediaType;
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
        $file = $this->faker->uuid().'.jpg';

        return [
            'post_id' => Post::factory(),
            'source_type' => IdeaMediaSourceType::Upload->value,
            'media_type' => IdeaMediaType::Image->value,
            'kind' => IdeaMediaKind::ConceptImage->value,
            'disk' => 'public',
            'original_name' => $file,
            'file_name' => $file,
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => $this->faker->numberBetween(20000, 400000),
            'path' => 'ideas/'.$file,
            'url' => 'https://cdn.example.com/ideas/'.$file,
            'preview_url' => 'https://cdn.example.com/ideas/'.$file,
            'thumbnail_url' => 'https://cdn.example.com/ideas/'.$file,
            'external_url' => null,
            'alt_text' => $this->faker->sentence(6),
            'metadata' => null,
            'sort_order' => $this->faker->numberBetween(0, 3),
        ];
    }
}
