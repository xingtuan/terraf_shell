<?php

namespace App\Services;

use App\Models\User;
use App\Support\StorageUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MediaService
{
    /**
     * Store a community attachment and return the legacy idea_media payload.
     *
     * @return array<string, mixed>
     */
    public function storeIdeaAttachment(UploadedFile $file, string $directory): array
    {
        $stored = $this->upload($file, $this->resolveLegacyCategory($directory, 'community'));
        $isImage = $this->isImageMime($stored['mime']);

        return [
            'disk' => $this->disk(),
            'original_name' => $stored['original_name'],
            'file_name' => basename($stored['path']),
            'extension' => strtolower((string) pathinfo($stored['path'], PATHINFO_EXTENSION)),
            'mime_type' => $stored['mime'],
            'size_bytes' => $stored['size'],
            'path' => $stored['path'],
            'url' => $stored['url'],
            'preview_url' => $isImage ? $stored['url'] : null,
            'thumbnail_url' => $isImage ? $stored['url'] : null,
        ];
    }

    /**
     * Store a CMS asset and return the legacy CMS payload.
     *
     * @return array<string, string>
     */
    public function storeCmsAsset(UploadedFile $file, string $directory): array
    {
        $stored = $this->upload($file, $this->resolveLegacyCategory($directory, 'general'));

        return [
            'media_path' => $stored['path'],
            'media_url' => $stored['url'],
        ];
    }

    /**
     * Store an avatar upload and return the legacy profile payload.
     *
     * @return array<string, string>
     */
    public function storeAvatar(UploadedFile $file, User $user): array
    {
        $stored = $this->upload($file, 'avatars');

        return [
            'avatar_path' => $stored['path'],
            'avatar_url' => $stored['url'],
        ];
    }

    /**
     * Upload a file to the configured storage disk.
     *
     * @return array{url: string, path: string, type: string, mime: string, size: int, original_name: string}
     */
    public function upload(UploadedFile $file, ?string $category = null): array
    {
        $mime = (string) ($file->getClientMimeType() ?: $file->getMimeType() ?: 'application/octet-stream');
        $type = $this->type($mime, $file);
        $normalizedCategory = $this->normalizeCategory($category);
        $extension = $this->extension($file);
        $filename = (string) Str::uuid().($extension !== '' ? '.'.$extension : '');
        $path = sprintf('%s/%s/%s/%s', $type, $normalizedCategory, now()->format('Y/m'), $filename);

        $contents = file_get_contents((string) $file->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('Unable to read the uploaded file.');
        }

        Storage::disk($this->disk())->put($path, $contents, 'public');

        return [
            'url' => $this->url($path),
            'path' => $path,
            'type' => $type,
            'mime' => $mime,
            'size' => (int) ($file->getSize() ?? 0),
            'original_name' => $file->getClientOriginalName(),
        ];
    }

    /**
     * Delete a stored file from the configured disk.
     */
    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        Storage::disk($this->disk())->delete($path);
    }

    /**
     * Move a stored file to a new path on the configured disk.
     */
    public function move(string $oldPath, string $newPath): bool
    {
        return Storage::disk($this->disk())->move($oldPath, $newPath);
    }

    /**
     * Delete a stored file from the provided disk or the configured disk.
     */
    public function deletePath(?string $path, ?string $disk = null): void
    {
        if (blank($path)) {
            return;
        }

        Storage::disk($disk ?: $this->disk())->delete($path);
    }

    /**
     * Resolve the upload disk for community media.
     */
    public function disk(): string
    {
        return (string) config('community.uploads.disk', env('COMMUNITY_UPLOAD_DISK', 'azure'));
    }

    /**
     * Resolve the public URL for a stored path.
     */
    public function url(string $path): string
    {
        return (string) StorageUrl::resolve($path, $this->disk());
    }

    /**
     * Resolve a stable public URL for a stored path.
     */
    public function publicUrl(string $path): string
    {
        return (string) StorageUrl::publicResolve($path, $this->disk());
    }

    /**
     * Determine the type folder for a file upload.
     */
    private function type(string $mime, UploadedFile $file): string
    {
        $normalizedMime = strtolower($mime);
        $extension = $this->extension($file);

        if (str_starts_with($normalizedMime, 'image/')) {
            return 'images';
        }

        if (str_starts_with($normalizedMime, 'video/')) {
            return 'videos';
        }

        if (str_starts_with($normalizedMime, 'audio/')) {
            return 'audios';
        }

        if ($this->isDocument($normalizedMime, $extension)) {
            return 'documents';
        }

        return 'others';
    }

    /**
     * Determine whether the upload should be stored as a document.
     */
    private function isDocument(string $mime, string $extension): bool
    {
        if (in_array($mime, [
            'application/pdf',
            'application/msword',
            'application/vnd.ms-excel',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ], true)) {
            return true;
        }

        return in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true);
    }

    /**
     * Resolve the filename extension for the uploaded file.
     */
    private function extension(UploadedFile $file): string
    {
        return strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin'));
    }

    /**
     * Normalize a storage category for path usage.
     */
    private function normalizeCategory(?string $category): string
    {
        $normalized = Str::of((string) ($category ?: 'general'))
            ->trim()
            ->lower()
            ->slug('-')
            ->value();

        return $normalized !== '' ? $normalized : 'general';
    }

    /**
     * Resolve a category from legacy directory-based callers.
     */
    private function resolveLegacyCategory(string $directory, string $fallback): string
    {
        $normalized = Str::of($directory)
            ->replace('\\', '/')
            ->lower()
            ->value();

        if (Str::contains($normalized, 'avatar')) {
            return 'avatars';
        }

        if (Str::contains($normalized, ['idea', 'community', 'post'])) {
            return 'community';
        }

        if (Str::contains($normalized, 'design')) {
            return 'designs';
        }

        if (Str::contains($normalized, ['product', 'material'])) {
            return 'products';
        }

        return $this->normalizeCategory($fallback);
    }

    /**
     * Determine whether the upload is an image.
     */
    private function isImageMime(string $mime): bool
    {
        return str_starts_with(strtolower($mime), 'image/');
    }
}
