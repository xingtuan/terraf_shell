<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key',
        'locale',
        'name',
        'subject',
        'preheader',
        'html_body',
        'text_body',
        'available_variables',
        'is_active',
        'version',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'available_variables' => 'array',
            'is_active' => 'boolean',
            'version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $template): void {
            if (! $template->isDirty(['subject', 'preheader', 'html_body', 'text_body', 'available_variables'])) {
                return;
            }

            EmailTemplateVersion::query()->create([
                'email_template_id' => $template->id,
                'key' => $template->getOriginal('key'),
                'locale' => $template->getOriginal('locale'),
                'version' => (int) $template->getOriginal('version'),
                'subject' => $template->getOriginal('subject'),
                'preheader' => $template->getOriginal('preheader'),
                'html_body' => $template->getOriginal('html_body'),
                'text_body' => $template->getOriginal('text_body'),
                'available_variables' => $template->getOriginal('available_variables'),
                'updated_by_id' => $template->getOriginal('updated_by_id'),
            ]);

            $template->version = ((int) $template->version) + 1;
        });
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EmailTemplateVersion::class);
    }
}
