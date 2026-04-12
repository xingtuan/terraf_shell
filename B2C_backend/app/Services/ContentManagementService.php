<?php

namespace App\Services;

use App\Enums\PublishStatus;
use App\Models\Article;
use App\Models\HomeSection;
use App\Models\Material;
use App\Models\MaterialApplication;
use App\Models\MaterialSpec;
use App\Models\MaterialStorySection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContentManagementService
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    public function listPublicMaterials(array $filters = []): Collection
    {
        return Material::query()
            ->published()
            ->when(
                array_key_exists('featured', $filters),
                fn ($query) => $query->where('is_featured', (bool) $filters['featured'])
            )
            ->withCount([
                'specs as specs_count' => fn ($query) => $query->published(),
                'storySections as story_sections_count' => fn ($query) => $query->published(),
                'applications as applications_count' => fn ($query) => $query->published(),
            ])
            ->ordered()
            ->get();
    }

    public function findPublicMaterial(string $identifier): Material
    {
        return $this->findByIdOrSlug(
            Material::class,
            $identifier,
            Material::query()
                ->published()
                ->with([
                    'specs' => fn ($query) => $query->published()->ordered(),
                    'storySections' => fn ($query) => $query->published()->ordered(),
                    'applications' => fn ($query) => $query->published()->ordered(),
                ])
                ->withCount([
                    'specs as specs_count' => fn ($query) => $query->published(),
                    'storySections as story_sections_count' => fn ($query) => $query->published(),
                    'applications as applications_count' => fn ($query) => $query->published(),
                ])
        );
    }

    public function listPublicArticles(array $filters): LengthAwarePaginator
    {
        return Article::query()
            ->published()
            ->when(
                filled($filters['category'] ?? null),
                fn ($query) => $query->where('category', $filters['category'])
            )
            ->ordered()
            ->paginate($this->perPage($filters['per_page'] ?? null))
            ->withQueryString();
    }

    public function findPublicArticle(string $identifier): Article
    {
        return $this->findByIdOrSlug(
            Article::class,
            $identifier,
            Article::query()->published()
        );
    }

    public function listPublicHomeSections(): Collection
    {
        return HomeSection::query()
            ->published()
            ->ordered()
            ->get();
    }

    public function getHomepageContent(): array
    {
        $featuredMaterials = $this->listPublicMaterials(['featured' => true]);

        if ($featuredMaterials->isEmpty()) {
            $featuredMaterials = $this->listPublicMaterials()->take(3)->values();
        }

        $latestArticles = Article::query()
            ->published()
            ->orderByDesc('published_at')
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        return [
            'home_sections' => $this->listPublicHomeSections(),
            'materials' => $featuredMaterials,
            'articles' => $latestArticles,
        ];
    }

    public function listMaterialsForAdmin(array $filters): LengthAwarePaginator
    {
        return Material::query()
            ->withCount(['specs', 'storySections', 'applications'])
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($searchQuery) use ($filters): void {
                    $searchQuery
                        ->where('title', 'like', '%'.$filters['search'].'%')
                        ->orWhere('slug', 'like', '%'.$filters['search'].'%')
                        ->orWhere('headline', 'like', '%'.$filters['search'].'%');
                })
            )
            ->when(
                filled($filters['status'] ?? null),
                fn ($query) => $query->where('status', $filters['status'])
            )
            ->when(
                array_key_exists('featured', $filters),
                fn ($query) => $query->where('is_featured', (bool) $filters['featured'])
            )
            ->ordered()
            ->paginate($this->perPage($filters['per_page'] ?? null))
            ->withQueryString();
    }

    public function createMaterial(array $data): Material
    {
        return DB::transaction(function () use ($data): Material {
            $attributes = $this->applyPublishState([
                'title' => $data['title'],
                'slug' => $this->uniqueSlug(Material::class, $data['slug'] ?? $data['title']),
                'headline' => $data['headline'] ?? null,
                'summary' => $data['summary'] ?? null,
                'story_overview' => $data['story_overview'] ?? null,
                'science_overview' => $data['science_overview'] ?? null,
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'sort_order' => $data['sort_order'] ?? 0,
            ], $data);

            $material = Material::query()->create($attributes);
            $this->syncMedia($material, $data, 'cms/materials');

            return $this->loadMaterial($material);
        });
    }

    public function updateMaterial(Material $material, array $data): Material
    {
        return DB::transaction(function () use ($material, $data): Material {
            $attributes = $this->applyPublishState([
                'title' => $data['title'] ?? $material->title,
                'headline' => $data['headline'] ?? $material->headline,
                'summary' => $data['summary'] ?? $material->summary,
                'story_overview' => $data['story_overview'] ?? $material->story_overview,
                'science_overview' => $data['science_overview'] ?? $material->science_overview,
                'is_featured' => $data['is_featured'] ?? $material->is_featured,
                'sort_order' => $data['sort_order'] ?? $material->sort_order,
            ], $data, $material);

            if (array_key_exists('slug', $data) || array_key_exists('title', $data)) {
                $attributes['slug'] = $this->uniqueSlug(
                    Material::class,
                    $data['slug'] ?? $data['title'] ?? $material->title,
                    $material->id
                );
            }

            $material->fill($attributes);
            $material->save();

            $this->syncMedia($material, $data, 'cms/materials');

            return $this->loadMaterial($material);
        });
    }

    public function deleteMaterial(Material $material): void
    {
        DB::transaction(function () use ($material): void {
            $material->loadMissing(['specs', 'storySections', 'applications']);

            foreach ($material->specs as $spec) {
                $this->deleteMedia($spec);
            }

            foreach ($material->storySections as $section) {
                $this->deleteMedia($section);
            }

            foreach ($material->applications as $application) {
                $this->deleteMedia($application);
            }

            $this->deleteMedia($material);
            $material->delete();
        });
    }

    public function listMaterialSpecsForAdmin(array $filters): LengthAwarePaginator
    {
        return MaterialSpec::query()
            ->with('material')
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($searchQuery) use ($filters): void {
                    $searchQuery
                        ->where('label', 'like', '%'.$filters['search'].'%')
                        ->orWhere('key', 'like', '%'.$filters['search'].'%')
                        ->orWhere('value', 'like', '%'.$filters['search'].'%');
                })
            )
            ->when(
                filled($filters['status'] ?? null),
                fn ($query) => $query->where('status', $filters['status'])
            )
            ->when(
                filled($filters['material_id'] ?? null),
                fn ($query) => $query->where('material_id', $filters['material_id'])
            )
            ->ordered()
            ->paginate($this->perPage($filters['per_page'] ?? null))
            ->withQueryString();
    }

    public function createMaterialSpec(array $data): MaterialSpec
    {
        return DB::transaction(function () use ($data): MaterialSpec {
            $attributes = $this->applyPublishState([
                'material_id' => $data['material_id'],
                'key' => $data['key'] ?? null,
                'label' => $data['label'],
                'value' => $data['value'],
                'unit' => $data['unit'] ?? null,
                'detail' => $data['detail'] ?? null,
                'icon' => $data['icon'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
            ], $data);

            $spec = MaterialSpec::query()->create($attributes);
            $this->syncMedia($spec, $data, 'cms/material-specs');

            return $spec->fresh()->load('material');
        });
    }

    public function updateMaterialSpec(MaterialSpec $spec, array $data): MaterialSpec
    {
        return DB::transaction(function () use ($spec, $data): MaterialSpec {
            $attributes = $this->applyPublishState([
                'material_id' => $data['material_id'] ?? $spec->material_id,
                'key' => $data['key'] ?? $spec->key,
                'label' => $data['label'] ?? $spec->label,
                'value' => $data['value'] ?? $spec->value,
                'unit' => $data['unit'] ?? $spec->unit,
                'detail' => $data['detail'] ?? $spec->detail,
                'icon' => $data['icon'] ?? $spec->icon,
                'sort_order' => $data['sort_order'] ?? $spec->sort_order,
            ], $data, $spec);

            $spec->fill($attributes);
            $spec->save();

            $this->syncMedia($spec, $data, 'cms/material-specs');

            return $spec->fresh()->load('material');
        });
    }

    public function deleteMaterialSpec(MaterialSpec $spec): void
    {
        DB::transaction(function () use ($spec): void {
            $this->deleteMedia($spec);
            $spec->delete();
        });
    }

    public function listMaterialStorySectionsForAdmin(array $filters): LengthAwarePaginator
    {
        return MaterialStorySection::query()
            ->with('material')
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($searchQuery) use ($filters): void {
                    $searchQuery
                        ->where('title', 'like', '%'.$filters['search'].'%')
                        ->orWhere('subtitle', 'like', '%'.$filters['search'].'%')
                        ->orWhere('content', 'like', '%'.$filters['search'].'%');
                })
            )
            ->when(
                filled($filters['status'] ?? null),
                fn ($query) => $query->where('status', $filters['status'])
            )
            ->when(
                filled($filters['material_id'] ?? null),
                fn ($query) => $query->where('material_id', $filters['material_id'])
            )
            ->ordered()
            ->paginate($this->perPage($filters['per_page'] ?? null))
            ->withQueryString();
    }

    public function createMaterialStorySection(array $data): MaterialStorySection
    {
        return DB::transaction(function () use ($data): MaterialStorySection {
            $attributes = $this->applyPublishState([
                'material_id' => $data['material_id'],
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'content' => $data['content'],
                'highlight' => $data['highlight'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
            ], $data);

            $section = MaterialStorySection::query()->create($attributes);
            $this->syncMedia($section, $data, 'cms/material-story-sections');

            return $section->fresh()->load('material');
        });
    }

    public function updateMaterialStorySection(MaterialStorySection $section, array $data): MaterialStorySection
    {
        return DB::transaction(function () use ($section, $data): MaterialStorySection {
            $attributes = $this->applyPublishState([
                'material_id' => $data['material_id'] ?? $section->material_id,
                'title' => $data['title'] ?? $section->title,
                'subtitle' => $data['subtitle'] ?? $section->subtitle,
                'content' => $data['content'] ?? $section->content,
                'highlight' => $data['highlight'] ?? $section->highlight,
                'sort_order' => $data['sort_order'] ?? $section->sort_order,
            ], $data, $section);

            $section->fill($attributes);
            $section->save();

            $this->syncMedia($section, $data, 'cms/material-story-sections');

            return $section->fresh()->load('material');
        });
    }

    public function deleteMaterialStorySection(MaterialStorySection $section): void
    {
        DB::transaction(function () use ($section): void {
            $this->deleteMedia($section);
            $section->delete();
        });
    }

    public function listMaterialApplicationsForAdmin(array $filters): LengthAwarePaginator
    {
        return MaterialApplication::query()
            ->with('material')
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($searchQuery) use ($filters): void {
                    $searchQuery
                        ->where('title', 'like', '%'.$filters['search'].'%')
                        ->orWhere('subtitle', 'like', '%'.$filters['search'].'%')
                        ->orWhere('description', 'like', '%'.$filters['search'].'%')
                        ->orWhere('audience', 'like', '%'.$filters['search'].'%');
                })
            )
            ->when(
                filled($filters['status'] ?? null),
                fn ($query) => $query->where('status', $filters['status'])
            )
            ->when(
                filled($filters['material_id'] ?? null),
                fn ($query) => $query->where('material_id', $filters['material_id'])
            )
            ->ordered()
            ->paginate($this->perPage($filters['per_page'] ?? null))
            ->withQueryString();
    }

    public function createMaterialApplication(array $data): MaterialApplication
    {
        return DB::transaction(function () use ($data): MaterialApplication {
            $attributes = $this->applyPublishState([
                'material_id' => $data['material_id'],
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'description' => $data['description'],
                'audience' => $data['audience'] ?? null,
                'cta_label' => $data['cta_label'] ?? null,
                'cta_url' => $data['cta_url'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
            ], $data);

            $application = MaterialApplication::query()->create($attributes);
            $this->syncMedia($application, $data, 'cms/material-applications');

            return $application->fresh()->load('material');
        });
    }

    public function updateMaterialApplication(MaterialApplication $application, array $data): MaterialApplication
    {
        return DB::transaction(function () use ($application, $data): MaterialApplication {
            $attributes = $this->applyPublishState([
                'material_id' => $data['material_id'] ?? $application->material_id,
                'title' => $data['title'] ?? $application->title,
                'subtitle' => $data['subtitle'] ?? $application->subtitle,
                'description' => $data['description'] ?? $application->description,
                'audience' => $data['audience'] ?? $application->audience,
                'cta_label' => $data['cta_label'] ?? $application->cta_label,
                'cta_url' => $data['cta_url'] ?? $application->cta_url,
                'sort_order' => $data['sort_order'] ?? $application->sort_order,
            ], $data, $application);

            $application->fill($attributes);
            $application->save();

            $this->syncMedia($application, $data, 'cms/material-applications');

            return $application->fresh()->load('material');
        });
    }

    public function deleteMaterialApplication(MaterialApplication $application): void
    {
        DB::transaction(function () use ($application): void {
            $this->deleteMedia($application);
            $application->delete();
        });
    }

    public function listArticlesForAdmin(array $filters): LengthAwarePaginator
    {
        return Article::query()
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($searchQuery) use ($filters): void {
                    $searchQuery
                        ->where('title', 'like', '%'.$filters['search'].'%')
                        ->orWhere('slug', 'like', '%'.$filters['search'].'%')
                        ->orWhere('excerpt', 'like', '%'.$filters['search'].'%');
                })
            )
            ->when(
                filled($filters['status'] ?? null),
                fn ($query) => $query->where('status', $filters['status'])
            )
            ->when(
                filled($filters['category'] ?? null),
                fn ($query) => $query->where('category', $filters['category'])
            )
            ->ordered()
            ->paginate($this->perPage($filters['per_page'] ?? null))
            ->withQueryString();
    }

    public function createArticle(array $data): Article
    {
        return DB::transaction(function () use ($data): Article {
            $attributes = $this->applyPublishState([
                'title' => $data['title'],
                'slug' => $this->uniqueSlug(Article::class, $data['slug'] ?? $data['title']),
                'excerpt' => $data['excerpt'] ?? null,
                'content' => $data['content'],
                'category' => $data['category'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
            ], $data);

            $article = Article::query()->create($attributes);
            $this->syncMedia($article, $data, 'cms/articles');

            return $article->fresh();
        });
    }

    public function updateArticle(Article $article, array $data): Article
    {
        return DB::transaction(function () use ($article, $data): Article {
            $attributes = $this->applyPublishState([
                'title' => $data['title'] ?? $article->title,
                'excerpt' => $data['excerpt'] ?? $article->excerpt,
                'content' => $data['content'] ?? $article->content,
                'category' => $data['category'] ?? $article->category,
                'sort_order' => $data['sort_order'] ?? $article->sort_order,
            ], $data, $article);

            if (array_key_exists('slug', $data) || array_key_exists('title', $data)) {
                $attributes['slug'] = $this->uniqueSlug(
                    Article::class,
                    $data['slug'] ?? $data['title'] ?? $article->title,
                    $article->id
                );
            }

            $article->fill($attributes);
            $article->save();

            $this->syncMedia($article, $data, 'cms/articles');

            return $article->fresh();
        });
    }

    public function deleteArticle(Article $article): void
    {
        DB::transaction(function () use ($article): void {
            $this->deleteMedia($article);
            $article->delete();
        });
    }

    public function listHomeSectionsForAdmin(array $filters): LengthAwarePaginator
    {
        return HomeSection::query()
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($searchQuery) use ($filters): void {
                    $searchQuery
                        ->where('key', 'like', '%'.$filters['search'].'%')
                        ->orWhere('title', 'like', '%'.$filters['search'].'%')
                        ->orWhere('subtitle', 'like', '%'.$filters['search'].'%');
                })
            )
            ->when(
                filled($filters['status'] ?? null),
                fn ($query) => $query->where('status', $filters['status'])
            )
            ->ordered()
            ->paginate($this->perPage($filters['per_page'] ?? null))
            ->withQueryString();
    }

    public function createHomeSection(array $data): HomeSection
    {
        return DB::transaction(function () use ($data): HomeSection {
            $attributes = $this->applyPublishState([
                'key' => $this->uniqueKey($data['key']),
                'title' => $data['title'] ?? null,
                'subtitle' => $data['subtitle'] ?? null,
                'content' => $data['content'] ?? null,
                'cta_label' => $data['cta_label'] ?? null,
                'cta_url' => $data['cta_url'] ?? null,
                'payload' => $data['payload'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
            ], $data);

            $section = HomeSection::query()->create($attributes);
            $this->syncMedia($section, $data, 'cms/home-sections');

            return $section->fresh();
        });
    }

    public function updateHomeSection(HomeSection $section, array $data): HomeSection
    {
        return DB::transaction(function () use ($section, $data): HomeSection {
            $attributes = $this->applyPublishState([
                'title' => $data['title'] ?? $section->title,
                'subtitle' => $data['subtitle'] ?? $section->subtitle,
                'content' => $data['content'] ?? $section->content,
                'cta_label' => $data['cta_label'] ?? $section->cta_label,
                'cta_url' => $data['cta_url'] ?? $section->cta_url,
                'payload' => $data['payload'] ?? $section->payload,
                'sort_order' => $data['sort_order'] ?? $section->sort_order,
            ], $data, $section);

            if (array_key_exists('key', $data)) {
                $attributes['key'] = $this->uniqueKey($data['key'], $section->id);
            }

            $section->fill($attributes);
            $section->save();

            $this->syncMedia($section, $data, 'cms/home-sections');

            return $section->fresh();
        });
    }

    public function deleteHomeSection(HomeSection $section): void
    {
        DB::transaction(function () use ($section): void {
            $this->deleteMedia($section);
            $section->delete();
        });
    }

    public function loadMaterial(Material $material): Material
    {
        return $material->fresh()
            ->load([
                'specs' => fn ($query) => $query->ordered(),
                'storySections' => fn ($query) => $query->ordered(),
                'applications' => fn ($query) => $query->ordered(),
            ])
            ->loadCount(['specs', 'storySections', 'applications']);
    }

    private function applyPublishState(array $attributes, array $data, ?Model $existing = null): array
    {
        $status = $data['status'] ?? $existing?->status ?? PublishStatus::Draft->value;

        $attributes['status'] = $status;

        if ($status === PublishStatus::Published->value) {
            if (array_key_exists('published_at', $data) && $data['published_at'] !== null) {
                $attributes['published_at'] = $data['published_at'];
            } elseif ($existing?->published_at !== null && $existing->status === PublishStatus::Published->value) {
                $attributes['published_at'] = $existing->published_at;
            } else {
                $attributes['published_at'] = now();
            }
        } else {
            $attributes['published_at'] = null;
        }

        return $attributes;
    }

    private function syncMedia(Model $model, array $data, string $directory): void
    {
        if (($data['remove_media'] ?? false) && filled($model->media_path)) {
            $this->mediaService->deletePath($model->media_path);
            $model->forceFill([
                'media_path' => null,
                'media_url' => null,
            ])->save();
        }

        if (! array_key_exists('media', $data) || $data['media'] === null) {
            return;
        }

        $this->mediaService->deletePath($model->media_path);

        $upload = $this->mediaService->storeCmsAsset(
            $data['media'],
            $directory.'/'.$model->getKey()
        );

        $model->forceFill($upload)->save();
    }

    private function deleteMedia(Model $model): void
    {
        $this->mediaService->deletePath($model->media_path);
    }

    private function findByIdOrSlug(string $modelClass, string $identifier, $query): Model
    {
        $record = $query
            ->where(function ($builder) use ($identifier): void {
                if (ctype_digit($identifier)) {
                    $builder
                        ->whereKey((int) $identifier)
                        ->orWhere('slug', $identifier);

                    return;
                }

                $builder->where('slug', $identifier);
            })
            ->first();

        if ($record === null) {
            throw $this->notFound($modelClass, $identifier);
        }

        return $record;
    }

    private function uniqueSlug(string $modelClass, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        $slug = $base === '' ? 'item' : $base;
        $original = $slug;
        $counter = 2;

        while (
            $modelClass::query()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function uniqueKey(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value, '_');
        $key = $base === '' ? 'section' : $base;
        $original = $key;
        $counter = 2;

        while (
            HomeSection::query()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('key', $key)
                ->exists()
        ) {
            $key = $original.'_'.$counter;
            $counter++;
        }

        return $key;
    }

    private function perPage(null|int|string $requested): int
    {
        $default = (int) config('community.pagination.default_per_page', 20);
        $max = (int) config('community.pagination.max_per_page', 50);
        $value = (int) ($requested ?: $default);

        return max(1, min($value, $max));
    }

    private function notFound(string $model, int|string $id): ModelNotFoundException
    {
        return (new ModelNotFoundException)->setModel($model, [$id]);
    }
}
