<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\DeleteMediaRequest;
use App\Http\Requests\Media\UploadMediaRequest;
use App\Http\Resources\MediaFileResource;
use App\Services\MediaFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class UploadController extends Controller
{
    public function __construct(
        private readonly MediaFileService $mediaFileService,
    ) {}

    /**
     * Upload a media file and return its persisted metadata.
     */
    public function upload(UploadMediaRequest $request): JsonResponse
    {
        $validated = $request->validated();

        /** @var UploadedFile $file */
        $file = $request->file('file');

        try {
            $mediaFile = $this->mediaFileService->upload(
                $request->user(),
                $file,
                $validated['category'] ?? null
            );
        } catch (RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), [], 500);
        }

        return $this->successResponse(new MediaFileResource($mediaFile));
    }

    /**
     * Delete a media file owned by the authenticated user or an admin user.
     */
    public function destroy(DeleteMediaRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->mediaFileService->delete(
            $request->user(),
            (string) $validated['path']
        );

        return $this->successResponse(null);
    }
}
