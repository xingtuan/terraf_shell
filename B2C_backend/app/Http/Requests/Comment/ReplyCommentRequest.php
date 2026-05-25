<?php

namespace App\Http\Requests\Comment;

use App\Services\CommunitySettingsService;
use App\Support\CommunityContentValidation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReplyCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
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
                $linkCount = CommunityContentValidation::countExternalLinks($this->input('content'));

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
