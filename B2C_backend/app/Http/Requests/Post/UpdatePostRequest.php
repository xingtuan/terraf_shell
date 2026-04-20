<?php

namespace App\Http\Requests\Post;

use App\Enums\IdeaMediaKind;
use App\Enums\IdeaMediaType;
use App\Models\IdeaMedia;
use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post instanceof Post && ($this->user()?->can('update', $post) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxFileSize = (int) config('community.idea_media.max_file_size_kb', 10240);
        $maxFiles = (int) config('community.idea_media.max_files', 12);
        $maxExternalLinks = (int) config('community.idea_media.max_external_links', 4);

        return [
            'title' => ['sometimes', 'required', 'string', 'max:100'],
            'content' => ['sometimes', 'required', 'string', 'min:20'],
            'excerpt' => ['nullable', 'string', 'max:400'],
            'funding_url' => ['nullable', 'url', 'max:2048'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => ['nullable', 'string', 'max:255'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['image', 'max:'.$maxFileSize],
            'image_alts' => ['nullable', 'array'],
            'image_alts.*' => ['nullable', 'string', 'max:150'],
            'attachments' => ['nullable', 'array', 'max:'.$maxFiles],
            'attachments.*' => ['file', 'mimes:'.$this->allowedAttachmentExtensionsRule(), 'max:'.$maxFileSize],
            'attachment_titles' => ['nullable', 'array'],
            'attachment_titles.*' => ['nullable', 'string', 'max:150'],
            'attachment_alts' => ['nullable', 'array'],
            'attachment_alts.*' => ['nullable', 'string', 'max:150'],
            'attachment_kinds' => ['nullable', 'array'],
            'attachment_kinds.*' => ['nullable', 'string', 'in:'.implode(',', IdeaMediaKind::uploadValues())],
            'model_3d_links' => ['nullable', 'array', 'max:'.$maxExternalLinks],
            'model_3d_links.*.url' => ['required', 'url', 'max:2048'],
            'model_3d_links.*.title' => ['nullable', 'string', 'max:150'],
            'model_3d_links.*.alt_text' => ['nullable', 'string', 'max:150'],
            'model_3d_links.*.kind' => ['nullable', 'string', 'in:'.implode(',', IdeaMediaKind::externalValues())],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer', 'exists:idea_media,id'],
            'remove_media_ids' => ['nullable', 'array'],
            'remove_media_ids.*' => ['integer', 'exists:idea_media,id'],
            'replace_media' => ['nullable', 'array'],
            'replace_media.*.id' => ['required', 'integer', 'exists:idea_media,id'],
            'replace_media.*.file' => ['nullable', 'file', 'mimes:'.$this->allowedAttachmentExtensionsRule(), 'max:'.$maxFileSize],
            'replace_media.*.external_url' => ['nullable', 'url', 'max:2048'],
            'replace_media.*.title' => ['nullable', 'string', 'max:150'],
            'replace_media.*.alt_text' => ['nullable', 'string', 'max:150'],
            'replace_media.*.kind' => ['nullable', 'string', 'in:'.implode(',', IdeaMediaKind::values())],
            'is_pinned' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateAttachmentKinds($validator, 'attachments', 'attachment_kinds');
                $this->validateReplacementPayload($validator);
                $this->validateMediaOwnership($validator);
            },
        ];
    }

    private function allowedAttachmentExtensionsRule(): string
    {
        return implode(',', config('community.idea_media.allowed_extensions', []));
    }

    private function detectUploadType(UploadedFile $file): IdeaMediaType
    {
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension()));

        if (in_array($extension, config('community.idea_media.image_extensions', []), true)) {
            return IdeaMediaType::Image;
        }

        return IdeaMediaType::Document;
    }

    private function validateAttachmentKinds(Validator $validator, string $filesKey, string $kindsKey): void
    {
        $files = $this->file($filesKey, []);
        $kinds = $this->input($kindsKey, []);

        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $kind = $kinds[$index] ?? null;

            if ($kind === null || $kind === '') {
                continue;
            }

            $mediaKind = IdeaMediaKind::tryFrom((string) $kind);

            if ($mediaKind === null || ! $mediaKind->supportsType($this->detectUploadType($file))) {
                $validator->errors()->add(
                    $kindsKey.'.'.$index,
                    'The selected attachment kind does not match the uploaded file type.'
                );
            }
        }
    }

    private function validateReplacementPayload(Validator $validator): void
    {
        $replacements = $this->all()['replace_media'] ?? [];

        foreach ($replacements as $index => $replacement) {
            $file = data_get($replacement, 'file');
            $externalUrl = data_get($replacement, 'external_url');
            $kind = IdeaMediaKind::tryFrom((string) data_get($replacement, 'kind'));
            $hasFile = $file instanceof UploadedFile;
            $hasExternalUrl = filled($externalUrl);

            if ($hasFile === $hasExternalUrl) {
                $validator->errors()->add(
                    'replace_media.'.$index,
                    'Each media replacement must include either a file or an external URL.'
                );

                continue;
            }

            if ($hasFile && $kind !== null && ! $kind->supportsType($this->detectUploadType($file))) {
                $validator->errors()->add(
                    'replace_media.'.$index.'.kind',
                    'The selected replacement kind does not match the uploaded file type.'
                );
            }

            if ($hasExternalUrl && $kind !== null && ! $kind->supportsType(IdeaMediaType::External3d)) {
                $validator->errors()->add(
                    'replace_media.'.$index.'.kind',
                    'External media replacements currently support only 3D model links.'
                );
            }
        }

        $removeIds = collect(array_merge(
            $this->input('remove_image_ids', []),
            $this->input('remove_media_ids', [])
        ))->map(static fn (mixed $value): int => (int) $value);

        $replaceIds = collect($replacements)
            ->pluck('id')
            ->filter()
            ->map(static fn (mixed $value): int => (int) $value);

        if ($removeIds->intersect($replaceIds)->isNotEmpty()) {
            $validator->errors()->add(
                'replace_media',
                'A media item cannot be removed and replaced in the same request.'
            );
        }
    }

    private function validateMediaOwnership(Validator $validator): void
    {
        $post = $this->route('post');

        if (! $post instanceof Post) {
            return;
        }

        $mediaIds = collect(array_merge(
            $this->input('remove_image_ids', []),
            $this->input('remove_media_ids', []),
            collect($this->input('replace_media', []))->pluck('id')->all()
        ))
            ->filter()
            ->map(static fn (mixed $value): int => (int) $value)
            ->unique()
            ->values();

        if ($mediaIds->isEmpty()) {
            return;
        }

        $ownedIds = IdeaMedia::query()
            ->where('post_id', $post->id)
            ->whereIn('id', $mediaIds)
            ->pluck('id')
            ->map(static fn (mixed $value): int => (int) $value);

        if ($ownedIds->count() !== $mediaIds->count()) {
            $validator->errors()->add(
                'media',
                'One or more media items do not belong to this post.'
            );
        }
    }
}
