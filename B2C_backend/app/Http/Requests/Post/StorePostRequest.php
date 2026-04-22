<?php

namespace App\Http\Requests\Post;

use App\Enums\IdeaMediaKind;
use App\Enums\IdeaMediaType;
use App\Models\Post;
use App\Rules\ValidTiptapDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
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
    public function rules(): array
    {
        $maxFileSize = (int) config('community.idea_media.max_file_size_kb', 10240);
        $maxFiles = (int) config('community.idea_media.max_files', 12);
        $maxExternalLinks = (int) config('community.idea_media.max_external_links', 4);

        return [
            'title' => ['required', 'string', 'max:100'],
            'content' => ['nullable', 'string', 'min:20', 'required_without:content_json'],
            'content_json' => ['nullable', 'string', new ValidTiptapDocument()],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'funding_url' => ['nullable', 'url', 'max:2048'],
            'cover_image_url' => ['nullable', 'url', 'max:2048'],
            'cover_image_path' => ['nullable', 'string', 'max:1024'],
            'reading_time' => ['nullable', 'integer', 'min:0', 'max:999'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => [$this->tagsRule()],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['image', 'max:'.$maxFileSize],
            'image_alts' => ['nullable', 'array'],
            'image_alts.*' => ['nullable', 'string', 'max:150'],
            'attachments' => ['nullable', 'array', 'max:'.$maxFiles],
            'attachments.*' => ['file', 'extensions:'.$this->allowedAttachmentExtensionsRule(), 'max:'.$maxFileSize],
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

    private function tagsRule(): ValidationRule|\Closure|string
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            if (is_string($value)) {
                if (mb_strlen($value) > 255) {
                    $fail('The '.$attribute.' field must not be greater than 255 characters.');
                }

                return;
            }

            if (! is_array($value)) {
                $fail('The '.$attribute.' field must be a string or an array.');

                return;
            }

            foreach ($value as $tag) {
                if (! is_string($tag) || trim($tag) === '' || mb_strlen($tag) > 120) {
                    $fail('Each tag must be a non-empty string with a maximum length of 120 characters.');

                    return;
                }
            }
        };
    }
}
