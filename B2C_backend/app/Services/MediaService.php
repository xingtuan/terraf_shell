<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    public function storeAvatar(UploadedFile $file, User $user): array
    {
        $path = $file->store('avatars/'.$user->id, $this->disk());

        return [
            'avatar_path' => $path,
            'avatar_url' => $this->url($path),
        ];
    }

    public function storePostImage(
        UploadedFile $file,
        Post $post,
        int $sortOrder,
        ?string $altText = null
    ): PostImage {
        $path = $file->store('posts/'.$post->id, $this->disk());

        return $post->images()->create([
            'path' => $path,
            'url' => $this->url($path),
            'alt_text' => $altText,
            'sort_order' => $sortOrder,
        ]);
    }

    public function deletePath(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk($this->disk())->delete($path);
    }

    private function url(string $path): string
    {
        return Storage::disk($this->disk())->url($path);
    }

    private function disk(): string
    {
        return (string) config('community.uploads.disk');
    }
}
