<?php

namespace App\Http\Resources;

use App\Models\ProductAttributeAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductAttributeAssignment */
class ProductAttributeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $definition = $this->definition;
        $value = $this->attributeValue;
        $locale = (string) $request->query('locale', 'en');

        return [
            'key' => $definition?->key,
            'label' => $this->translated($definition?->label_translations, $definition?->label, $locale),
            'value' => $this->rawValue(),
            'display_label' => $this->translated($value?->label_translations, $this->displayValue(), $locale),
            'type' => $definition?->type,
            'unit' => $definition?->unit,
            'group' => $definition?->group,
            'help_text' => $definition?->help_text,
            'color_hex' => $value?->color_hex,
            'is_filterable' => (bool) $definition?->is_filterable,
            'is_variant_option' => (bool) $definition?->is_variant_option,
            'is_searchable' => (bool) $definition?->is_searchable,
            'is_specification' => (bool) $definition?->is_specification,
            'allows_multiple' => (bool) $definition?->allows_multiple,
        ];
    }

    /**
     * @param  array<string, string>|null  $translations
     */
    private function translated(?array $translations, ?string $fallback, string $locale): ?string
    {
        return $translations[$locale] ?? $translations['en'] ?? $fallback;
    }
}
