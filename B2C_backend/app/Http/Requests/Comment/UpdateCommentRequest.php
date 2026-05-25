<?php

namespace App\Http\Requests\Comment;

use App\Models\Comment;
use App\Services\CommunitySettingsService;
use App\Support\CommunityContentValidation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $comment = $this->route('comment');

        return $comment instanceof Comment && ($this->user()?->can('update', $comment) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required_without:body', 'nullable', 'string', 'max:5000'],
            'body' => ['required_without:content', 'nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $maxLinks = app(CommunitySettingsService::class)->maxExternalLinks();
                $linkCount = CommunityContentValidation::countExternalLinks([
                    $this->input('content'),
                    $this->input('body'),
                ]);

                if ($linkCount > $maxLinks) {
                    $validator->errors()->add(
                        'content',
                        __('api.community.too_many_external_links', ['max' => $maxLinks])
                    );
                }
            },
        ];
    }
}
