<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class ProductAttributeAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'attribute_definition_id',
        'product_attribute_value_id',
        'value_text',
        'value_number',
        'value_boolean',
        'value_json',
    ];

    protected function casts(): array
    {
        return [
            'value_number' => 'decimal:4',
            'value_boolean' => 'boolean',
            'value_json' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $assignment): void {
            if ($assignment->product_id === null || $assignment->attribute_definition_id === null) {
                return;
            }

            $definition = $assignment->relationLoaded('definition')
                ? $assignment->definition
                : ProductAttributeDefinition::query()->find($assignment->attribute_definition_id);

            if ($definition?->allows_multiple) {
                return;
            }

            $duplicateExists = self::query()
                ->where('product_id', $assignment->product_id)
                ->where('attribute_definition_id', $assignment->attribute_definition_id)
                ->when($assignment->exists, fn ($query) => $query->whereKeyNot($assignment->getKey()))
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'attribute_definition_id' => ['This attribute can only be assigned once to a product.'],
                ]);
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeDefinition::class, 'attribute_definition_id');
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeValue::class, 'product_attribute_value_id');
    }

    public function displayValue(): ?string
    {
        if ($this->attributeValue !== null) {
            return $this->attributeValue->label;
        }

        if ($this->value_text !== null && trim($this->value_text) !== '') {
            return $this->value_text;
        }

        if ($this->value_number !== null) {
            return (string) $this->value_number;
        }

        if ($this->value_boolean !== null) {
            return $this->value_boolean ? 'Yes' : 'No';
        }

        if (is_array($this->value_json) && $this->value_json !== []) {
            return collect($this->value_json)
                ->map(fn (mixed $value, string|int $key): ?string => is_scalar($value)
                    ? (is_string($key) ? str($key)->headline().': '.$value : (string) $value)
                    : null)
                ->filter()
                ->implode(', ');
        }

        return null;
    }

    public function rawValue(): mixed
    {
        return $this->attributeValue?->value
            ?? $this->value_text
            ?? $this->value_number
            ?? $this->value_boolean
            ?? $this->value_json;
    }
}
