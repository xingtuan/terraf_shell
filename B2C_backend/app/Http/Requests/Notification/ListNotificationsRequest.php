<?php

namespace App\Http\Requests\Notification;

use App\Enums\NotificationType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListNotificationsRequest extends FormRequest
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
            'read' => ['nullable', Rule::in(['all', 'read', 'unread'])],
            'type' => ['nullable', Rule::in(NotificationType::values())],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('community.pagination.max_per_page')],
        ];
    }
}
