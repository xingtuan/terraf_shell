<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppSetting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'is_secret',
        'is_encrypted',
        'description',
        'options',
        'validation_rules',
        'is_public',
        'updated_by',
    ];

    protected $hidden = [
        'value',
    ];

    protected function casts(): array
    {
        return [
            'is_secret' => 'boolean',
            'is_encrypted' => 'boolean',
            'options' => 'array',
            'validation_rules' => 'array',
            'is_public' => 'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function fullKey(): string
    {
        return "{$this->group}.{$this->key}";
    }
}
