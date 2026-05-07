<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'attribute_definition_id',
        'value',
        'label',
        'label_translations',
        'color_hex',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'label_translations' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $value): void {
            $value->value = str($value->value)->trim()->slug('_')->toString();
            $value->color_hex = filled($value->color_hex) ? strtoupper((string) $value->color_hex) : null;
        });
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeDefinition::class, 'attribute_definition_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProductAttributeAssignment::class, 'product_attribute_value_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_active'), true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy($query->qualifyColumn('sort_order'))
            ->orderBy($query->qualifyColumn('label'));
    }
}
