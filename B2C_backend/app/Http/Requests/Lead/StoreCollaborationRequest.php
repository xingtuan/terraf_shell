<?php

namespace App\Http\Requests\Lead;

use Illuminate\Contracts\Validation\ValidationRule;

class StoreCollaborationRequest extends BaseLeadRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'organization_type' => ['required', 'string', 'max:80'],
            'collaboration_goal' => ['required', 'string', 'max:1000'],
            'project_stage' => ['nullable', 'string', 'max:120'],
            'timeline' => ['nullable', 'string', 'max:120'],
            'metadata' => ['nullable', 'array'],
        ]);
    }
}
