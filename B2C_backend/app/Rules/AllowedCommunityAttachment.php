<?php

namespace App\Rules;

use App\Services\CommunitySettingsService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class AllowedCommunityAttachment implements ValidationRule
{
    public function __construct(
        private readonly CommunitySettingsService $communitySettings,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail(__('api.community.invalid_attachment_extension'));

            return;
        }

        $extension = strtolower(trim(ltrim((string) $value->getClientOriginalExtension(), '.')));

        if ($extension === '' || ! in_array($extension, $this->communitySettings->allowedExtensions(), true)) {
            $fail(__('api.community.invalid_attachment_extension'));
        }
    }
}
