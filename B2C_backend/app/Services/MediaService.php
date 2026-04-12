<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    public function storeIdeaAttachment(UploadedFile $file, string $directory): array
    {
        $stored = $this->storeFile($file, $directory);
        $isImage = $this->isImageFile($file);

        return [
            'disk' => $stored['disk'],
            'original_name' => $stored['original_name'],
            'file_name' => $stored['file_name'],
            'extension' => $stored['extension'],
            'mime_type' => $stored['mime_type'],
            'size_bytes' => $stored['size_bytes'],
            'path' => $stored['path'],
            'url' => $stored['url'],
            'preview_url' => $isImage ? $stored['url'] : null,
            'thumbnail_url' => $isImage ? $stored['url'] : null,
        ];
    }

    public function storeCmsAsset(UploadedFile $file, string $directory): array
    {
        $stored = $this->storeFile($file, $directory);

        return [
            'media_path' => $stored['path'],
            'media_url' => $stored['url'],
        ];
    }

    public function storeAvatar(UploadedFile $file, User $user): array
    {
        $stored = $this->storeFile($file, 'avatars/'.$user->id);

        return [
            'avatar_path' => $stored['path'],
            'avatar_url' => $stored['url'],
        ];
    }

    public function deletePath(?string $path, ?string $disk = null): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk($disk ?: $this->disk())->delete($path);
    }

    private function url(string $path): string
    {
        return Storage::disk($this->disk())->url($path);
    }

    private function disk(): string
    {
        return (string) config('community.uploads.disk');
    }

    /**
     * @return array<string, mixed>
     */
    private function storeFile(UploadedFile $file, string $directory): array
    {
        $path = $file->store($directory, $this->disk());

        return [
            'disk' => $this->disk(),
            'original_name' => $file->getClientOriginalName(),
            'file_name' => basename($path),
            'extension' => strtolower((string) pathinfo($path, PATHINFO_EXTENSION)),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => (int) ($file->getSize() ?? 0),
            'path' => $path,
            'url' => $this->url($path),
        ];
    }

    private function isImageFile(UploadedFile $file): bool
    {
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension()));

        return in_array($extension, config('community.idea_media.image_extensions', []), true);
    }
}
