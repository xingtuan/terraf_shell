<?php

namespace Database\Factories;

use App\Enums\IdeaMediaKind;
use App\Enums\IdeaMediaSourceType;
use App\Enums\IdeaMediaType;
use App\Models\IdeaMedia;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IdeaMedia>
 */
class IdeaMediaFactory extends Factory
{
    protected $model = IdeaMedia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $file = fake()->uuid().'.jpg';

        return [
            'post_id' => Post::factory(),
            'source_type' => IdeaMediaSourceType::Upload->value,
            'media_type' => IdeaMediaType::Image->value,
            'kind' => IdeaMediaKind::ConceptImage->value,
            'title' => fake()->sentence(3),
            'alt_text' => fake()->sentence(6),
            'disk' => 'public',
            'original_name' => $file,
            'file_name' => $file,
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(15000, 350000),
            'path' => 'ideas/'.$file,
            'url' => 'https://cdn.example.com/ideas/'.$file,
            'preview_url' => 'https://cdn.example.com/ideas/'.$file,
            'thumbnail_url' => 'https://cdn.example.com/ideas/'.$file,
            'external_url' => null,
            'metadata' => null,
            'sort_order' => fake()->numberBetween(0, 5),
        ];
    }

    public function document(): static
    {
        $file = fake()->uuid().'.pdf';

        return $this->state(fn (): array => [
            'media_type' => IdeaMediaType::Document->value,
            'kind' => IdeaMediaKind::PdfPresentation->value,
            'original_name' => $file,
            'file_name' => $file,
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'path' => 'ideas/'.$file,
            'url' => 'https://cdn.example.com/ideas/'.$file,
            'preview_url' => null,
            'thumbnail_url' => null,
        ]);
    }

    public function externalModel(): static
    {
        return $this->state(fn (): array => [
            'source_type' => IdeaMediaSourceType::ExternalUrl->value,
            'media_type' => IdeaMediaType::External3d->value,
            'kind' => IdeaMediaKind::Model3d->value,
            'disk' => null,
            'original_name' => null,
            'file_name' => null,
            'extension' => null,
            'mime_type' => null,
            'size_bytes' => null,
            'path' => null,
            'url' => 'https://sketchfab.com/models/sample-model',
            'preview_url' => null,
            'thumbnail_url' => null,
            'external_url' => 'https://sketchfab.com/models/sample-model',
        ]);
    }
}
