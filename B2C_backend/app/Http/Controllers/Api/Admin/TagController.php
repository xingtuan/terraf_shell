<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListTagsRequest;
use App\Http\Requests\Admin\StoreTagRequest;
use App\Http\Requests\Admin\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Services\TaxonomyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(ListTagsRequest $request, TaxonomyService $taxonomyService): JsonResponse
    {
        $this->ensureAdmin($request);

        $tags = $taxonomyService->listTagsForAdmin($request->validated());

        return $this->paginatedResponse(
            $tags,
            TagResource::collection($tags->getCollection())
        );
    }

    public function store(StoreTagRequest $request, TaxonomyService $taxonomyService): JsonResponse
    {
        $tag = $taxonomyService->createTag($request->validated());

        return $this->successResponse(
            new TagResource($tag),
            'Tag created successfully.',
            201
        );
    }

    public function show(Request $request, Tag $tag): JsonResponse
    {
        $this->ensureAdmin($request);

        $tag->loadCount(['posts as posts_count' => fn ($query) => $query->approved()]);

        return $this->successResponse(new TagResource($tag));
    }

    public function update(
        UpdateTagRequest $request,
        Tag $tag,
        TaxonomyService $taxonomyService
    ): JsonResponse {
        $tag = $taxonomyService->updateTag($tag, $request->validated());
        $tag->loadCount(['posts as posts_count' => fn ($query) => $query->approved()]);

        return $this->successResponse(
            new TagResource($tag),
            'Tag updated successfully.'
        );
    }

    public function destroy(Request $request, Tag $tag, TaxonomyService $taxonomyService): JsonResponse
    {
        $this->ensureAdmin($request);
        $taxonomyService->deleteTag($tag);

        return $this->successResponse(null, 'Tag deleted successfully.');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
