<?php

namespace App\Models;

use Database\Factories\UserViolationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserViolation extends Model
{
    /** @use HasFactory<UserViolationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'actor_user_id',
        'resolved_by',
        'report_id',
        'subject_type',
        'subject_id',
        'type',
        'severity',
        'status',
        'reason',
        'resolution_note',
        'metadata',
        'occurred_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
