<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttributeDefinition extends Model
{
    use HasFactory;

    public const TYPE_OPTIONS = [
        'text' => 'Text',
        'select' => 'Select',
        'multiselect' => 'Multiselect',
        'number' => 'Number',
        'boolean' => 'Boolean',
        'rich_text' => 'Rich text',
        'json' => 'JSON',
    ];

    protected $fillable = [
        'key',
        'label',
        'label_translations',
        'type',
        'unit',
        'group',
        'help_text',
        'is_variant_option',
        'is_filterable',
        'is_searchable',
        'is_specification',
        'is_required',
        'allows_multiple',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'label_translations' => 'array',
            'is_variant_option' => 'boolean',
            'is_filterable' => 'boolean',
            'is_searchable' => 'boolean',
            'is_specification' => 'boolean',
            'is_required' => 'boolean',
            'allows_multiple' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $definition): void {
            $definition->key = str($definition->key)->trim()->slug('_')->toString();
            $definition->type = array_key_exists((string) $definition->type, self::TYPE_OPTIONS)
                ? (string) $definition->type
                : 'select';
        });
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_definition_id')
            ->orderBy('sort_order')
            ->orderBy('label');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProductAttributeAssignment::class, 'attribute_definition_id');
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
