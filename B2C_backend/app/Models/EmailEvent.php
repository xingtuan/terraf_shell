<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailEvent extends Model
{
    protected $fillable = [
        'key',
        'category',
        'name',
        'description',
        'is_enabled',
        'recipient_type',
        'custom_recipients',
        'template_key',
        'throttle_minutes',
        'use_queue',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'custom_recipients' => 'array',
            'throttle_minutes' => 'integer',
            'use_queue' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function lastLog(): ?EmailLog
    {
        return EmailLog::query()
            ->where('event_key', $this->key)
            ->latest()
            ->first();
    }
}
