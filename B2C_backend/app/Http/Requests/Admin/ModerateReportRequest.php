<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReportResolutionAction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModerateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canModerate() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'internal_note' => ['nullable', 'string', 'max:2000'],
            'moderator_note' => ['nullable', 'string', 'max:2000'],
            'public_note' => ['nullable', 'string', 'max:2000'],
            'resolution_action' => ['nullable', Rule::in(ReportResolutionAction::values())],
        ];
    }

    public function internalNote(): ?string
    {
        return $this->validated('internal_note') ?? $this->validated('moderator_note');
    }

    public function publicNote(): ?string
    {
        return $this->validated('public_note');
    }
}
