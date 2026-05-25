<?php

namespace App\Http\Requests\Post;

use App\Enums\IdeaMediaKind;
use App\Enums\IdeaMediaType;
use App\Models\IdeaMedia;
use App\Models\Post;
use App\Rules\ExternalSafeUrl;
use App\Rules\ValidTiptapDocument;
use App\Services\CommunitySettingsService;
use App\Support\CommunityContentValidation;
use App\Support\MediaUploadRules;
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
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cover_image_url' => filled($this->input('cover_image_url')) ? $this->input('cover_image_url') : null,
            'cover_image_path' => filled($this->input('cover_image_path')) ? $this->input('cover_image_path') : null,
            'cover_image_disk' => filled($this->input('cover_image_disk')) ? $this->input('cover_image_disk') : null,
        ]);
    }

    public function rules(): array
    {
        $communitySettings = app(CommunitySettingsService::class);
        $maxFiles = $communitySettings->maxFiles();
        $maxExternalLinks = $communitySettings->maxExternalLinks();
        $hasLocalCoverPath = filled($this->input('cover_image_path'));

        return [
            'title' => ['sometimes', 'required', 'string', 'max:100'],
            'content' => ['sometimes', 'required', 'string', 'min:20'],
            'content_json' => ['nullable', 'string', new ValidTiptapDocument],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'funding_url' => ['nullable', 'url:http,https', new ExternalSafeUrl, 'max:2048'],
            'cover_image_url' => array_merge(
                ['nullable', 'max:2048'],
                $hasLocalCoverPath ? [] : ['url'],
            ),
            'cover_image_path' => ['nullable', 'string', 'max:1024', 'not_regex:/\.\.|[\\\\]/'],
            'cover_image_disk' => ['nullable', 'string', 'max:255'],
            'reading_time' => ['nullable', 'integer', 'min:0', 'max:999'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => [$this->tagsRule()],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'images' => ['nullable', 'array', 'max:'.$maxFiles],
            'images.*' => MediaUploadRules::optionalImageRules(),
            'image_alts' => ['nullable', 'array'],
            'image_alts.*' => ['nullable', 'string', 'max:150'],
            'attachments' => ['nullable', 'array', 'max:'.$maxFiles],
            'attachments.*' => MediaUploadRules::optionalAttachmentRules(),
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
            'replace_media.*.file' => MediaUploadRules::optionalAttachmentRules(),
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
                $this->validateTotalUploads($validator);
                $this->validateExternalLinks($validator);
            },
        ];
    }

    private function validateTotalUploads(Validator $validator): void
    {
        $post = $this->route('post');

        if (! $post instanceof Post) {
            return;
        }

        $maxFiles = app(CommunitySettingsService::class)->maxFiles();
        $removeIds = collect(array_merge(
            $this->input('remove_image_ids', []),
            $this->input('remove_media_ids', [])
        ))
            ->filter()
            ->map(static fn (mixed $value): int => (int) $value)
            ->unique();

        $existingUploadCount = (int) $post->media()
            ->whereNotNull('path')
            ->when($removeIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $removeIds))
            ->count();

        $newUploadCount = CommunityContentValidation::countUploadedFiles($this->file('images', []))
            + CommunityContentValidation::countUploadedFiles($this->file('attachments', []))
            + $this->replacementUploadsThatIncreaseFileCount($post);
        $effectiveCoverPath = $this->has('cover_image_path')
            ? $this->input('cover_image_path')
            : $post->cover_image_path;
        $totalFiles = $existingUploadCount
            + $newUploadCount
            + (filled($effectiveCoverPath) ? 1 : 0);

        if ($totalFiles > $maxFiles) {
            $validator->errors()->add(
                'attachments',
                __('api.community.too_many_files', ['max' => $maxFiles])
            );
        }
    }

    private function replacementUploadsThatIncreaseFileCount(Post $post): int
    {
        $replacements = $this->all()['replace_media'] ?? [];

        if (! is_array($replacements)) {
            return 0;
        }

        $replacementIdsWithFiles = collect($replacements)
            ->filter(static fn (mixed $replacement): bool => data_get($replacement, 'file') instanceof UploadedFile)
            ->pluck('id')
            ->filter()
            ->map(static fn (mixed $value): int => (int) $value)
            ->unique()
            ->values();

        if ($replacementIdsWithFiles->isEmpty()) {
            return 0;
        }

        $existingUploadIds = IdeaMedia::query()
            ->where('post_id', $post->id)
            ->whereIn('id', $replacementIdsWithFiles)
            ->whereNotNull('path')
            ->pluck('id')
            ->map(static fn (mixed $value): int => (int) $value)
            ->flip();

        return $replacementIdsWithFiles
            ->reject(static fn (int $id): bool => $existingUploadIds->has($id))
            ->count();
    }

    private function validateExternalLinks(Validator $validator): void
    {
        $maxLinks = app(CommunitySettingsService::class)->maxExternalLinks();
        $linkCount = CommunityContentValidation::countExternalLinks([
            'title' => $this->input('title'),
            'excerpt' => $this->input('excerpt'),
            'content' => $this->input('content'),
            'content_json' => $this->input('content_json'),
            'funding_url' => $this->input('funding_url'),
            'cover_image_url' => $this->input('cover_image_url'),
            'model_3d_links' => $this->input('model_3d_links', []),
            'replace_media' => $this->input('replace_media', []),
        ]);

        if ($linkCount > $maxLinks) {
            $validator->errors()->add(
                'content',
                __('api.community.too_many_external_links', ['max' => $maxLinks])
            );
        }
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
                    __('api.community.attachment_kind_mismatch')
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
                    __('api.community.replacement_required')
                );

                continue;
            }

            if ($hasFile && $kind !== null && ! $kind->supportsType($this->detectUploadType($file))) {
                $validator->errors()->add(
                    'replace_media.'.$index.'.kind',
                    __('api.community.replacement_kind_mismatch')
                );
            }

            if ($hasExternalUrl && $kind !== null && ! $kind->supportsType(IdeaMediaType::External3d)) {
                $validator->errors()->add(
                    'replace_media.'.$index.'.kind',
                    __('api.community.external_replacement_kind')
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
                __('api.community.remove_and_replace_conflict')
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
                __('api.community.media_not_owned')
            );
        }
    }

    private function tagsRule(): ValidationRule|\Closure|string
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            if (is_string($value)) {
                if (mb_strlen($value) > 255) {
                    $fail(__('api.community.tags_too_long'));
                }

                return;
            }

            if (! is_array($value)) {
                $fail(__('api.community.tags_invalid_type'));

                return;
            }

            foreach ($value as $tag) {
                if (! is_string($tag) || trim($tag) === '' || mb_strlen($tag) > 120) {
                    $fail(__('api.community.tags_invalid_item'));

                    return;
                }
            }
        };
    }
}
