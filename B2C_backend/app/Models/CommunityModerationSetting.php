<?php

namespace App\Models;

use App\Enums\CommunitySubmissionPolicy;
use Illuminate\Database\Eloquent\Model;

class CommunityModerationSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'submission_policy' => CommunitySubmissionPolicy::class,
        ];
    }
}
