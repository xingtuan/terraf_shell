<?php

namespace App\Services;

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class MediaFileService
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    /**
     * Upload a file and persist its metadata.
     */
    public function upload(?User $user, UploadedFile $file, ?string $category = null): MediaFile
    {
        return DB::transaction(function () use ($user, $file, $category): MediaFile {
            $stored = $this->mediaService->upload($file, $category);
            $pathSegments = explode('/', $stored['path']);

            return MediaFile::query()->create([
                'user_id' => $user?->id,
                'original_name' => $stored['original_name'],
                'path' => $stored['path'],
                'url' => $this->mediaService->publicUrl($stored['path']),
                'type' => $stored['type'],
                'mime_type' => $stored['mime'],
                'size' => $stored['size'],
                'category' => $pathSegments[1] ?? 'general',
            ])->fresh();
        });
    }

    /**
     * Delete a stored file and its metadata record.
     *
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     * @throws QueryException
     */
    public function delete(User $user, string $path): void
    {
        $mediaFile = MediaFile::query()->where('path', $path)->firstOrFail();

        if (! $user->isAdmin() && (int) $mediaFile->user_id !== (int) $user->id) {
            throw new AuthorizationException('You are not authorized to delete this media file.');
        }

        DB::transaction(function () use ($mediaFile): void {
            $this->mediaService->delete($mediaFile->path);
            $mediaFile->delete();
        });
    }
}
