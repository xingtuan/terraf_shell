<?php

namespace App\Http\Requests\Notification;

use App\Models\UserNotification;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MarkNotificationReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $notification = $this->route('notification');

        return $notification instanceof UserNotification
            && ($this->user()?->can('update', $notification) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
