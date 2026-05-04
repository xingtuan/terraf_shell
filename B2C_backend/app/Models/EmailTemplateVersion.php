<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplateVersion extends Model
{
    protected $fillable = [
        'email_template_id',
        'key',
        'locale',
        'version',
        'subject',
        'preheader',
        'html_body',
        'text_body',
        'available_variables',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'available_variables' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
