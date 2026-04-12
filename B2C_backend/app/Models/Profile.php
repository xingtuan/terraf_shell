<?php

namespace App\Models;

use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bio',
        'location',
        'website',
        'school_or_company',
        'region',
        'portfolio_url',
        'open_to_collab',
        'avatar_path',
        'avatar_url',
    ];

    protected function casts(): array
    {
        return [
            'open_to_collab' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $profile): void {
            if (blank($profile->avatar_path)) {
                $profile->avatar_url = null;

                return;
            }

            $profile->avatar_url = Storage::disk((string) config('community.uploads.disk'))
                ->url($profile->avatar_path);
        });
    }

    protected function region(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): ?string => $value ?? ($attributes['location'] ?? null),
            set: fn (?string $value): array => [
                'region' => $value,
                'location' => $value,
            ],
        );
    }

    protected function location(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): ?string => $value ?? ($attributes['region'] ?? null),
            set: fn (?string $value): array => [
                'location' => $value,
                'region' => $value,
            ],
        );
    }

    protected function portfolioUrl(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): ?string => $value ?? ($attributes['website'] ?? null),
            set: fn (?string $value): array => [
                'portfolio_url' => $value,
                'website' => $value,
            ],
        );
    }

    protected function website(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): ?string => $value ?? ($attributes['portfolio_url'] ?? null),
            set: fn (?string $value): array => [
                'website' => $value,
                'portfolio_url' => $value,
            ],
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
