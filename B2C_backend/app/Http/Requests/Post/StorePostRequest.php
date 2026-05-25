<?php

namespace App\Http\Requests\Post;

use App\Enums\IdeaMediaKind;
use App\Enums\IdeaMediaType;
use App\Models\IdeaMedia;
use App\Models\Post;
use App\Rules\ExternalSafeUrl;
use App\Rules\ValidTiptapDocument;
use App\Services\CommunitySettingsService;
use App\Services\Settings\SettingsService;
use App\Support\CommunityContentValidation;
use App\Support\MediaUploadRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Post::class) ?? false;
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
            'title' => ['required', 'string', 'max:100'],
            'content' => ['nullable', 'string', 'min:20', 'required_without:content_json'],
            'content_json' => ['nullable', 'string', new ValidTiptapDocument],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'funding_url' => [
                'nullable',
                Rule::prohibitedIf(! app(SettingsService::class)->boolean('feature.funding_links_enabled', true)),
                'url:http,https',
                new ExternalSafeUrl,
                'max:2048',
            ],
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
                $this->validateTotalUploads($validator);
                $this->validateExternalLinks($validator);
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.max' => __('api.community.too_many_files', ['max' => app(CommunitySettingsService::class)->maxFiles()]),
            'attachments.*.max' => __('api.community.file_too_large'),
            'images.max' => __('api.community.too_many_files', ['max' => app(CommunitySettingsService::class)->maxFiles()]),
            'images.*.max' => __('api.community.file_too_large'),
        ];
    }

    private function validateTotalUploads(Validator $validator): void
    {
        $maxFiles = app(CommunitySettingsService::class)->maxFiles();
        $totalFiles = CommunityContentValidation::countUploadedFiles($this->file('images', []))
            + CommunityContentValidation::countUploadedFiles($this->file('attachments', []))
            + (filled($this->input('cover_image_path')) ? 1 : 0);

        if ($totalFiles > $maxFiles) {
            $validator->errors()->add(
                'attachments',
                __('api.community.too_many_files', ['max' => $maxFiles])
            );
        }
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

        return IdeaMedia::inferMediaTypeFromExtension($extension);
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
