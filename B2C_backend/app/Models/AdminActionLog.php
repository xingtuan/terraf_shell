<?php

namespace App\Models;

use Database\Factories\AdminActionLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminActionLog extends Model
{
    /** @use HasFactory<AdminActionLogFactory> */
    use HasFactory;

    protected $fillable = [
        'actor_user_id',
        'target_user_id',
        'subject_type',
        'subject_id',
        'action',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
