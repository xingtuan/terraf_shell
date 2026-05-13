<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'target_type',
        'target_id',
        'reason',
        'description',
        'status',
        'moderator_note',
        'public_note',
        'reviewed_by',
        'reviewed_at',
        'resolved_at',
        'dismissed_at',
        'completed_at',
        'reporter_notified_at',
        'resolution_action',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'dismissed_at' => 'datetime',
            'completed_at' => 'datetime',
            'reporter_notified_at' => 'datetime',
        ];
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, [
            ReportStatus::Resolved->value,
            ReportStatus::Dismissed->value,
        ], true);
    }

    public function isOpenForModeration(): bool
    {
        return in_array($this->status, [
            ReportStatus::Pending->value,
            ReportStatus::Reviewed->value,
        ], true);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    public function moderationLogs(): HasMany
    {
        return $this->hasMany(ModerationLog::class);
    }

    public function violations(): HasMany
    {
        return $this->hasMany(UserViolation::class);
    }
}
