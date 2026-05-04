<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'mailer',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'api_key',
        'domain',
        'region',
        'from_address',
        'from_name',
        'reply_to_address',
        'reply_to_name',
        'admin_recipients',
        'timeout',
        'use_queue',
        'created_by_id',
        'updated_by_id',
    ];

    protected $hidden = [
        'password',
        'api_key',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'port' => 'integer',
            'password' => 'encrypted',
            'api_key' => 'encrypted',
            'admin_recipients' => 'array',
            'timeout' => 'integer',
            'use_queue' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function maskedPassword(): ?string
    {
        return filled($this->password) ? '********' : null;
    }

    public function maskedApiKey(): ?string
    {
        return filled($this->api_key) ? '********' : null;
    }
}
