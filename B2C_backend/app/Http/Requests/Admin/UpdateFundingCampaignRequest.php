<?php

namespace App\Http\Requests\Admin;

use App\Enums\FundingCampaignStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFundingCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageUsers() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'support_enabled' => ['required', 'boolean'],
            'support_button_text' => ['nullable', 'string', 'max:120'],
            'external_crowdfunding_url' => [
                Rule::requiredIf($this->boolean('support_enabled')),
                'nullable',
                'url',
                'max:2048',
            ],
            'campaign_status' => ['required', Rule::in(FundingCampaignStatus::values())],
            'target_amount' => ['nullable', 'numeric', 'min:0'],
            'pledged_amount' => ['nullable', 'numeric', 'min:0'],
            'backer_count' => ['nullable', 'integer', 'min:0'],
            'reward_description' => ['nullable', 'string', 'max:5000'],
            'campaign_start_at' => ['nullable', 'date'],
            'campaign_end_at' => ['nullable', 'date', 'after_or_equal:campaign_start_at'],
        ];
    }
}
