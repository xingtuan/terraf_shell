<?php

namespace App\Http\Controllers\Api;

use App\Enums\IdeaMediaSourceType;
use App\Http\Controllers\Controller;
use App\Models\IdeaMedia;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostAttachmentDownloadController extends Controller
{
    public function __invoke(Request $request, string $identifier, IdeaMedia $media): StreamedResponse
    {
        $post = Post::query()
            ->with('user')
            ->where(function ($query) use ($identifier): void {
                if (ctype_digit($identifier)) {
                    $query->whereKey((int) $identifier);
                }

                $query->orWhere('slug', $identifier);
            })
            ->first();

        abort_if($post === null || ! $post->isVisibleTo($request->user('sanctum')), 404);
        abort_if(
            $media->post_id !== $post->id
                || $media->sourceTypeValue() !== IdeaMediaSourceType::Upload->value
                || blank($media->path),
            404
        );

        $disk = $media->disk ?: (string) config('community.uploads.disk');

        abort_unless(Storage::disk($disk)->exists($media->path), 404);

        $media->increment('download_count');

        $headers = [];

        if (filled($media->mime_type)) {
            $headers['Content-Type'] = $media->mime_type;
        }

        $filename = $media->original_name
            ?: $media->file_name
            ?: sprintf('attachment-%d', $media->id);

        return Storage::disk($disk)->download($media->path, $filename, $headers);
    }
}
