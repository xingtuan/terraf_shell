<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->addAttributeDefinitionMetadata();
        $this->ensureLegacyAttributeDefinitions();
        $this->backfillLegacyCategory();
        $this->backfillLegacyAttributeAssignments();
        $this->dropLegacyProductAttributeColumns();
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'category')) {
                $table->string('category')->nullable()->after('slug');
            }

            if (! Schema::hasColumn('products', 'model')) {
                $table->string('model')->nullable()->after('category');
            }

            if (! Schema::hasColumn('products', 'finish')) {
                $table->string('finish')->nullable()->after('model');
            }

            if (! Schema::hasColumn('products', 'color')) {
                $table->string('color')->nullable()->after('finish');
            }

            if (! Schema::hasColumn('products', 'technique')) {
                $table->string('technique')->nullable()->after('color');
            }

            if (! Schema::hasColumn('products', 'dimensions')) {
                $table->string('dimensions')->nullable()->after('is_new');
            }

            if (! Schema::hasColumn('products', 'dimensions_translations')) {
                $table->json('dimensions_translations')->nullable()->after('dimensions');
            }

            if (! Schema::hasColumn('products', 'specifications')) {
                $table->json('specifications')->nullable()->after('weight_grams');
            }

            if (! Schema::hasColumn('products', 'use_cases')) {
                $table->json('use_cases')->nullable()->after('product_faqs');
            }
        });
    }

    private function addAttributeDefinitionMetadata(): void
    {
        Schema::table('product_attribute_definitions', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_attribute_definitions', 'group')) {
                $table->string('group')->nullable()->after('unit');
            }

            if (! Schema::hasColumn('product_attribute_definitions', 'help_text')) {
                $table->text('help_text')->nullable()->after('group');
            }

            if (! Schema::hasColumn('product_attribute_definitions', 'allows_multiple')) {
                $table->boolean('allows_multiple')->default(false)->after('is_required');
            }
        });
    }

    private function ensureLegacyAttributeDefinitions(): void
    {
        $definitions = [
            'model' => [
                'label' => 'Model',
                'type' => 'select',
                'group' => 'Product',
                'is_filterable' => true,
                'is_searchable' => true,
                'is_specification' => true,
                'is_variant_option' => false,
                'allows_multiple' => false,
                'sort_order' => 40,
            ],
            'finish' => [
                'label' => 'Finish',
                'type' => 'select',
                'group' => 'Material',
                'is_filterable' => true,
                'is_searchable' => false,
                'is_specification' => true,
                'is_variant_option' => true,
                'allows_multiple' => false,
                'sort_order' => 30,
            ],
            'color' => [
                'label' => 'Color',
                'type' => 'select',
                'group' => 'Material',
                'is_filterable' => true,
                'is_searchable' => false,
                'is_specification' => true,
                'is_variant_option' => true,
                'allows_multiple' => false,
                'sort_order' => 20,
            ],
            'technique' => [
                'label' => 'Technique',
                'type' => 'select',
                'group' => 'Material',
                'is_filterable' => false,
                'is_searchable' => true,
                'is_specification' => true,
                'is_variant_option' => false,
                'allows_multiple' => false,
                'sort_order' => 50,
            ],
            'use_case' => [
                'label' => 'Use Case',
                'type' => 'multiselect',
                'group' => 'Application',
                'is_filterable' => true,
                'is_searchable' => true,
                'is_specification' => true,
                'is_variant_option' => false,
                'allows_multiple' => true,
                'sort_order' => 60,
            ],
            'application' => [
                'label' => 'Application',
                'type' => 'multiselect',
                'group' => 'Application',
                'is_filterable' => true,
                'is_searchable' => true,
                'is_specification' => true,
                'is_variant_option' => false,
                'allows_multiple' => true,
                'sort_order' => 65,
            ],
            'dimensions' => [
                'label' => 'Dimensions',
                'type' => 'text',
                'group' => 'Dimensions',
                'is_filterable' => false,
                'is_searchable' => false,
                'is_specification' => true,
                'is_variant_option' => false,
                'allows_multiple' => false,
                'sort_order' => 70,
            ],
        ];

        foreach ($definitions as $key => $definition) {
            DB::table('product_attribute_definitions')->updateOrInsert(
                ['key' => $key],
                [
                    'label' => $definition['label'],
                    'label_translations' => json_encode(['en' => $definition['label']], JSON_THROW_ON_ERROR),
                    'type' => $definition['type'],
                    'unit' => null,
                    'group' => $definition['group'],
                    'help_text' => 'Migrated from the previous fixed product attribute fields.',
                    'is_variant_option' => $definition['is_variant_option'],
                    'is_filterable' => $definition['is_filterable'],
                    'is_searchable' => $definition['is_searchable'],
                    'is_specification' => $definition['is_specification'],
                    'is_required' => false,
                    'allows_multiple' => $definition['allows_multiple'],
                    'sort_order' => $definition['sort_order'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function backfillLegacyCategory(): void
    {
        if (! Schema::hasColumn('products', 'category')) {
            return;
        }

        DB::table('products')
            ->whereNull('category_id')
            ->whereNotNull('category')
            ->orderBy('id')
            ->select(['id', 'category'])
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    $rawCategory = trim((string) $product->category);

                    if ($rawCategory === '') {
                        continue;
                    }

                    $slug = Str::slug($rawCategory, '_');
                    $label = Str::headline(str_replace('_', ' ', $rawCategory));
                    $categoryId = DB::table('product_categories')
                        ->where('slug', $slug)
                        ->orWhere('name', $label)
                        ->value('id');

                    if ($categoryId === null) {
                        $categoryId = DB::table('product_categories')->insertGetId([
                            'name' => $label,
                            'name_translations' => json_encode(['en' => $label], JSON_THROW_ON_ERROR),
                            'description' => $label.' category.',
                            'description_translations' => json_encode(['en' => $label.' category.'], JSON_THROW_ON_ERROR),
                            'slug' => $slug,
                            'sort_order' => 0,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['category_id' => $categoryId]);
                }
            });
    }

    private function backfillLegacyAttributeAssignments(): void
    {
        $definitionIds = DB::table('product_attribute_definitions')
            ->pluck('id', 'key')
            ->all();

        $columns = collect([
            'id',
            'model',
            'finish',
            'color',
            'technique',
            'use_cases',
            'dimensions',
            'specifications',
        ])
            ->filter(fn (string $column): bool => $column === 'id' || Schema::hasColumn('products', $column))
            ->values()
            ->all();

        DB::table('products')
            ->orderBy('id')
            ->select($columns)
            ->chunkById(100, function ($products) use (&$definitionIds): void {
                foreach ($products as $product) {
                    foreach (['model', 'finish', 'color', 'technique'] as $key) {
                        $this->assignPredefinedValue(
                            (int) $product->id,
                            $definitionIds[$key] ?? null,
                            $key,
                            $product->{$key} ?? null,
                        );
                    }

                    foreach ($this->decodeArray($product->use_cases ?? null) as $useCase) {
                        $this->assignPredefinedValue(
                            (int) $product->id,
                            $definitionIds['use_case'] ?? null,
                            'use_case',
                            $useCase,
                        );
                    }

                    $dimensions = trim((string) ($product->dimensions ?? ''));

                    if ($dimensions !== '' && isset($definitionIds['dimensions'])) {
                        $this->assignTextValue((int) $product->id, (int) $definitionIds['dimensions'], $dimensions);
                    }

                    foreach ($this->decodeSpecifications($product->specifications ?? null) as $entry) {
                        $key = $this->specificationKey($entry);
                        $label = trim((string) ($entry['label'] ?? Str::headline($key)));
                        $value = trim((string) ($entry['value'] ?? ''));

                        if ($key === '' || $label === '' || $value === '') {
                            continue;
                        }

                        $definitionIds[$key] = $this->ensureSpecificationDefinition(
                            $key,
                            $label,
                            isset($entry['unit']) && is_string($entry['unit']) ? trim($entry['unit']) : null,
                            isset($entry['group']) && is_string($entry['group']) ? trim($entry['group']) : null,
                        );

                        $this->assignTextValue((int) $product->id, (int) $definitionIds[$key], $value);
                    }
                }
            });
    }

    private function assignPredefinedValue(
        int $productId,
        ?int $definitionId,
        string $definitionKey,
        mixed $rawValue,
    ): void {
        if ($definitionId === null || ! is_string($rawValue) || trim($rawValue) === '') {
            return;
        }

        $value = Str::slug(trim($rawValue), '_');
        $label = Str::headline(str_replace('_', ' ', trim($rawValue)));
        $valueId = DB::table('product_attribute_values')->where([
            'attribute_definition_id' => $definitionId,
            'value' => $value,
        ])->value('id');

        if ($valueId === null) {
            $valueId = DB::table('product_attribute_values')->insertGetId([
                'attribute_definition_id' => $definitionId,
                'value' => $value,
                'label' => $label,
                'label_translations' => json_encode(['en' => $label], JSON_THROW_ON_ERROR),
                'color_hex' => $this->colorHex($definitionKey, $value),
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('product_attribute_assignments')->updateOrInsert(
            [
                'product_id' => $productId,
                'attribute_definition_id' => $definitionId,
                'product_attribute_value_id' => $valueId,
            ],
            [
                'value_text' => null,
                'value_number' => null,
                'value_boolean' => null,
                'value_json' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function assignTextValue(int $productId, int $definitionId, string $value): void
    {
        DB::table('product_attribute_assignments')->updateOrInsert(
            [
                'product_id' => $productId,
                'attribute_definition_id' => $definitionId,
                'product_attribute_value_id' => null,
            ],
            [
                'value_text' => $value,
                'value_number' => null,
                'value_boolean' => null,
                'value_json' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function ensureSpecificationDefinition(
        string $key,
        string $label,
        ?string $unit,
        ?string $group,
    ): int {
        $existingId = DB::table('product_attribute_definitions')
            ->where('key', $key)
            ->value('id');

        $payload = [
            'label' => $label,
            'label_translations' => json_encode(['en' => $label], JSON_THROW_ON_ERROR),
            'type' => 'text',
            'unit' => $unit !== '' ? $unit : null,
            'group' => $group !== '' ? $group : 'Specifications',
            'help_text' => 'Migrated from legacy product specifications.',
            'is_variant_option' => false,
            'is_filterable' => false,
            'is_searchable' => false,
            'is_specification' => true,
            'is_required' => false,
            'allows_multiple' => false,
            'sort_order' => 100,
            'is_active' => true,
            'updated_at' => now(),
        ];

        if ($existingId !== null) {
            DB::table('product_attribute_definitions')
                ->where('id', $existingId)
                ->update($payload);

            return (int) $existingId;
        }

        return (int) DB::table('product_attribute_definitions')->insertGetId([
            'key' => $key,
            ...$payload,
            'created_at' => now(),
        ]);
    }

    private function dropLegacyProductAttributeColumns(): void
    {
        $columns = collect([
            'category',
            'model',
            'finish',
            'color',
            'technique',
            'use_cases',
            'dimensions',
            'dimensions_translations',
            'specifications',
        ])
            ->filter(fn (string $column): bool => Schema::hasColumn('products', $column))
            ->values()
            ->all();

        if ($columns === []) {
            return;
        }

        Schema::table('products', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }

    /**
     * @return array<int, string>
     */
    private function decodeArray(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->filter(fn (mixed $item): bool => is_string($item) && trim($item) !== '')
                ->values()
                ->all();
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded)
            ? collect($decoded)
                ->filter(fn (mixed $item): bool => is_string($item) && trim($item) !== '')
                ->values()
                ->all()
            : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeSpecifications(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn (mixed $entry): bool => is_array($entry))
            ->values()
            ->all();
    }

    private function specificationKey(array $entry): string
    {
        $rawKey = isset($entry['key']) && is_string($entry['key']) && trim($entry['key']) !== ''
            ? $entry['key']
            : (string) ($entry['label'] ?? '');

        return Str::slug($rawKey, '_');
    }

    private function colorHex(string $definitionKey, string $value): ?string
    {
        if ($definitionKey !== 'color') {
            return null;
        }

        return match ($value) {
            'ocean_bone' => '#d8d4c6',
            'forged_ash' => '#66645d',
            'oyster_white' => '#f4f0e7',
            'warm_sand' => '#d5bea0',
            default => null,
        };
    }
};
