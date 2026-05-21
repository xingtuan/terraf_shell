<?php

namespace App\Http\Requests\Media;

use App\Support\MediaUploadRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => MediaUploadRules::genericFileRules($this->input('category')),
            'category' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimetypes' => 'The uploaded file type is not supported for this upload category.',
            'file.extensions' => 'The uploaded file extension is not supported for this upload category.',
            'file.max' => 'The uploaded file is too large for this upload category.',
            'file.image' => 'This upload category accepts only JPG, PNG, or WebP images.',
        ];
    }
}
