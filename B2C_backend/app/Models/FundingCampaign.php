<?php

namespace App\Models;

use App\Enums\FundingCampaignStatus;
use App\Models\Concerns\HasLocalizedAttributes;
use Database\Factories\FundingCampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundingCampaign extends Model
{
    /** @use HasFactory<FundingCampaignFactory> */
    use HasFactory, HasLocalizedAttributes;

    protected array $localizedAttributes = [
        'support_button_text',
        'reward_description',
    ];

    protected $fillable = [
        'post_id',
        'support_enabled',
        'support_button_text',
        'support_button_text_translations',
        'external_crowdfunding_url',
        'campaign_status',
        'target_amount',
        'pledged_amount',
        'backer_count',
        'reward_description',
        'reward_description_translations',
        'campaign_start_at',
        'campaign_end_at',
    ];

    protected function casts(): array
    {
        return [
            'support_enabled' => 'boolean',
            'support_button_text_translations' => 'array',
            'reward_description_translations' => 'array',
            'target_amount' => 'decimal:2',
            'pledged_amount' => 'decimal:2',
            'backer_count' => 'integer',
            'campaign_start_at' => 'datetime',
            'campaign_end_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function isVisibleTo(?User $viewer): bool
    {
        if ($viewer?->canModerate()) {
            return true;
        }

        return $this->campaign_status !== FundingCampaignStatus::Draft->value;
    }

    public function progressPercentage(): ?float
    {
        $target = $this->target_amount !== null ? (float) $this->target_amount : null;
        $pledged = $this->pledged_amount !== null ? (float) $this->pledged_amount : null;

        if ($target === null || $pledged === null || $target <= 0) {
            return null;
        }

        return round(($pledged / $target) * 100, 2);
    }
}
