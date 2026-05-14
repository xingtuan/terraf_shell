<?php

namespace App\Http\Requests\Content;

use App\Models\ProductAttributeDefinition;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ListPublishedProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', Rule::in(['featured', 'newest', 'best_selling', 'price_low_to_high', 'price_high_to_low'])],
            'category' => ['nullable', 'string', 'max:120'],
            'attributes' => ['nullable', 'array'],
            'attributes.*' => ['nullable'],
            'stock_status' => ['nullable', Rule::in(array_keys(ProductVariant::STOCK_STATUS_OPTIONS))],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'attributes' => $this->normalizedAttributeFilters(),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateCategoryFilter($validator);
            $this->validatePriceRange($validator);

            $attributes = $this->input('attributes', []);

            if (! is_array($attributes) || $attributes === []) {
                return;
            }

            $definitions = ProductAttributeDefinition::query()
                ->active()
                ->where('is_filterable', true)
                ->whereIn('key', array_keys($attributes))
                ->get()
                ->keyBy('key');

            foreach ($attributes as $key => $value) {
                if (! is_string($key) || ! $definitions->has($key)) {
                    $validator->errors()->add("attributes.{$key}", 'The selected attribute filter is not available.');

                    continue;
                }

                $definition = $definitions->get($key);

                if ($definition->type === 'number') {
                    $this->validateNumberAttribute($validator, $key, $value);
                } elseif ($definition->type === 'boolean') {
                    $this->validateBooleanAttribute($validator, $key, $value);
                } elseif (is_array($value)) {
                    foreach ($value as $item) {
                        if (! is_scalar($item)) {
                            $validator->errors()->add("attributes.{$key}", 'Attribute filter values must be scalar.');

                            break;
                        }
                    }
                } elseif ($value !== null && ! is_scalar($value)) {
                    $validator->errors()->add("attributes.{$key}", 'Attribute filter values must be scalar.');
                }
            }
        });
    }

    private function validateCategoryFilter(Validator $validator): void
    {
        $category = $this->input('category');

        if (! is_string($category) || trim($category) === '') {
            return;
        }

        $exists = ProductCategory::query()
            ->active()
            ->where(function ($query) use ($category): void {
                $query->where('slug', $category);

                if (ctype_digit($category)) {
                    $query->orWhere('id', (int) $category);
                }
            })
            ->exists();

        if (! $exists) {
            $validator->errors()->add('category', 'The selected category filter is not available.');
        }
    }

    private function validatePriceRange(Validator $validator): void
    {
        $priceMin = $this->input('price_min');
        $priceMax = $this->input('price_max');

        if (! is_numeric($priceMin) || ! is_numeric($priceMax)) {
            return;
        }

        if ((float) $priceMax < (float) $priceMin) {
            $validator->errors()->add('price_max', 'The price max field must be greater than or equal to price min.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizedAttributeFilters(): array
    {
        $singularAttributes = $this->input('attribute', []);
        $attributes = $this->input('attributes', []);

        $normalized = is_array($singularAttributes) ? $singularAttributes : [];

        if (is_array($attributes)) {
            $normalized = array_replace($normalized, $attributes);
        }

        foreach ([
            'model' => 'model',
            'finish' => 'finish',
            'color' => 'color',
            'use_case' => 'use_case',
        ] as $legacyKey => $attributeKey) {
            $value = $this->input($legacyKey);

            if (array_key_exists($attributeKey, $normalized) || $value === null || $value === '') {
                continue;
            }

            $normalized[$attributeKey] = $value;
        }

        return $normalized;
    }

    private function validateNumberAttribute(Validator $validator, string $key, mixed $value): void
    {
        if (is_array($value)) {
            foreach (['min', 'max'] as $rangeKey) {
                if (isset($value[$rangeKey]) && ! is_numeric($value[$rangeKey])) {
                    $validator->errors()->add("attributes.{$key}.{$rangeKey}", 'Attribute range filters must be numeric.');
                }
            }

            return;
        }

        if ($value !== null && $value !== '' && ! is_numeric($value)) {
            $validator->errors()->add("attributes.{$key}", 'Attribute filter values must be numeric.');
        }
    }

    private function validateBooleanAttribute(Validator $validator, string $key, mixed $value): void
    {
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
            $validator->errors()->add("attributes.{$key}", 'Attribute filter values must be true or false.');
        }
    }
}
