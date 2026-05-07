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
        Schema::create('product_attribute_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->json('label_translations')->nullable();
            $table->string('type')->default('select');
            $table->string('unit')->nullable();
            $table->boolean('is_variant_option')->default(false)->index();
            $table->boolean('is_filterable')->default(false)->index();
            $table->boolean('is_searchable')->default(false)->index();
            $table->boolean('is_specification')->default(true)->index();
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('product_attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attribute_definition_id')
                ->constrained('product_attribute_definitions')
                ->cascadeOnDelete();
            $table->string('value');
            $table->string('label');
            $table->json('label_translations')->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['attribute_definition_id', 'value'], 'product_attribute_value_definition_value_unique');
        });

        Schema::create('product_attribute_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('attribute_definition_id')
                ->constrained('product_attribute_definitions')
                ->cascadeOnDelete();
            $table->foreignId('product_attribute_value_id')
                ->nullable()
                ->constrained('product_attribute_values')
                ->nullOnDelete();
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 12, 4)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(
                ['product_id', 'attribute_definition_id', 'product_attribute_value_id'],
                'product_attribute_assignment_value_unique',
            );
            $table->index(['product_id', 'attribute_definition_id'], 'product_attribute_assignment_product_definition_index');
        });

        $definitionIds = $this->seedDefinitionsAndValues();
        $this->backfillAssignments($definitionIds);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_assignments');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attribute_definitions');
    }

    /**
     * @return array<string, int>
     */
    private function seedDefinitionsAndValues(): array
    {
        $definitions = [
            'material_family' => [
                'label' => 'Material Family',
                'type' => 'select',
                'is_variant_option' => true,
                'is_filterable' => true,
                'is_searchable' => true,
                'sort_order' => 10,
                'values' => [
                    'oxp' => 'OXP',
                    'cxp' => 'CXP',
                    'mxp' => 'MXP',
                ],
            ],
            'color' => [
                'label' => 'Color',
                'type' => 'select',
                'is_variant_option' => true,
                'is_filterable' => true,
                'sort_order' => 20,
                'values' => [
                    'ocean_bone' => 'Ocean Bone',
                    'forged_ash' => 'Forged Ash',
                    'oyster_white' => 'Oyster White',
                    'warm_sand' => 'Warm Sand',
                ],
            ],
            'finish' => [
                'label' => 'Finish',
                'type' => 'select',
                'is_variant_option' => true,
                'is_filterable' => true,
                'sort_order' => 30,
                'values' => [
                    'glossy' => 'Glossy',
                    'matte' => 'Matte',
                    'honed' => 'Honed',
                    'textured' => 'Textured',
                ],
            ],
            'model' => [
                'label' => 'Model',
                'type' => 'select',
                'is_variant_option' => true,
                'is_filterable' => true,
                'sort_order' => 40,
                'values' => [
                    'lite_15' => 'Lite 15',
                    'heritage_16' => 'Heritage 16',
                ],
            ],
            'technique' => [
                'label' => 'Technique',
                'type' => 'select',
                'is_filterable' => true,
                'is_searchable' => true,
                'sort_order' => 50,
                'values' => [
                    'compression_moulding' => 'Compression moulding',
                    'pelletizing' => 'Pelletizing',
                    'polishing' => 'Polishing',
                    'finishing' => 'Finishing',
                    'original_pure' => 'Original Pure',
                    'precision_inlay' => 'Precision Inlay',
                    'driftwood_blend' => 'Driftwood Blend',
                    'other' => 'Other',
                ],
            ],
            'use_case' => [
                'label' => 'Use Case',
                'type' => 'multiselect',
                'is_filterable' => true,
                'is_searchable' => true,
                'sort_order' => 60,
                'values' => [
                    'tableware' => 'Tableware',
                    'hospitality' => 'Hospitality',
                    'interior_object' => 'Interior object',
                    'sample_kit' => 'Sample kit',
                    'b2b_feedstock' => 'B2B feedstock',
                    'design_collaboration' => 'Design collaboration',
                    'home_dining' => 'Home Dining',
                    'hospitality_service' => 'Hospitality Service',
                    'retail_gifting' => 'Retail & Gifting',
                    'interior_styling' => 'Interior Styling',
                    'design_projects' => 'Design Projects',
                ],
            ],
            'size' => [
                'label' => 'Size',
                'type' => 'select',
                'is_variant_option' => true,
                'is_filterable' => true,
                'sort_order' => 70,
                'values' => [
                    's' => 'S',
                    'm' => 'M',
                    'l' => 'L',
                ],
            ],
            'application' => [
                'label' => 'Application',
                'type' => 'multiselect',
                'is_filterable' => true,
                'is_searchable' => true,
                'sort_order' => 80,
                'values' => [
                    'tableware' => 'Tableware',
                    'interiors' => 'Interiors',
                    'hospitality' => 'Hospitality',
                    'education' => 'Education',
                    'material_development' => 'Material development',
                ],
            ],
        ];

        $definitionIds = [];

        foreach ($definitions as $key => $definition) {
            $definitionIds[$key] = (int) DB::table('product_attribute_definitions')->insertGetId([
                'key' => $key,
                'label' => $definition['label'],
                'label_translations' => $this->translations($definition['label']),
                'type' => $definition['type'],
                'unit' => null,
                'is_variant_option' => (bool) ($definition['is_variant_option'] ?? false),
                'is_filterable' => (bool) ($definition['is_filterable'] ?? false),
                'is_searchable' => (bool) ($definition['is_searchable'] ?? false),
                'is_specification' => true,
                'is_required' => false,
                'sort_order' => (int) $definition['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($definition['values'] as $value => $label) {
                DB::table('product_attribute_values')->insert([
                    'attribute_definition_id' => $definitionIds[$key],
                    'value' => $value,
                    'label' => $label,
                    'label_translations' => $this->translations($label),
                    'color_hex' => $this->colorHex($value),
                    'sort_order' => count($definitionIds) * 10,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $definitionIds;
    }

    /**
     * @param  array<string, int>  $definitionIds
     */
    private function backfillAssignments(array $definitionIds): void
    {
        $valueIds = DB::table('product_attribute_values')
            ->join('product_attribute_definitions', 'product_attribute_definitions.id', '=', 'product_attribute_values.attribute_definition_id')
            ->select([
                'product_attribute_definitions.key as definition_key',
                'product_attribute_values.value',
                'product_attribute_values.id',
            ])
            ->get()
            ->groupBy('definition_key')
            ->map(fn ($values) => $values->pluck('id', 'value')->all())
            ->all();

        DB::table('products')
            ->orderBy('id')
            ->select(['id', 'model', 'finish', 'color', 'technique', 'use_cases'])
            ->chunkById(100, function ($products) use ($definitionIds, $valueIds): void {
                foreach ($products as $product) {
                    $this->assignValue((int) $product->id, $definitionIds, $valueIds, 'material_family', 'oxp');
                    $this->assignValue((int) $product->id, $definitionIds, $valueIds, 'model', $product->model);
                    $this->assignValue((int) $product->id, $definitionIds, $valueIds, 'finish', $product->finish);
                    $this->assignValue((int) $product->id, $definitionIds, $valueIds, 'color', $product->color);
                    $this->assignValue((int) $product->id, $definitionIds, $valueIds, 'technique', $product->technique);

                    foreach ($this->decodeArray($product->use_cases) as $useCase) {
                        $this->assignValue((int) $product->id, $definitionIds, $valueIds, 'use_case', $useCase);
                    }
                }
            });
    }

    /**
     * @param  array<string, int>  $definitionIds
     * @param  array<string, array<string, int>>  $valueIds
     */
    private function assignValue(
        int $productId,
        array $definitionIds,
        array $valueIds,
        string $definitionKey,
        mixed $rawValue,
    ): void {
        if (! isset($definitionIds[$definitionKey]) || ! is_string($rawValue) || trim($rawValue) === '') {
            return;
        }

        $value = Str::slug(trim($rawValue), '_');
        $valueId = $valueIds[$definitionKey][$value] ?? null;

        if ($valueId === null) {
            return;
        }

        DB::table('product_attribute_assignments')->updateOrInsert(
            [
                'product_id' => $productId,
                'attribute_definition_id' => $definitionIds[$definitionKey],
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

    /**
     * @return array<int, string>
     */
    private function decodeArray(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)->filter(fn (mixed $item): bool => is_string($item) && trim($item) !== '')->values()->all();
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded)
            ? collect($decoded)->filter(fn (mixed $item): bool => is_string($item) && trim($item) !== '')->values()->all()
            : [];
    }

    private function translations(string $label): string
    {
        return json_encode(['en' => $label], JSON_THROW_ON_ERROR);
    }

    private function colorHex(string $value): ?string
    {
        return match ($value) {
            'ocean_bone' => '#d8d4c6',
            'forged_ash' => '#66645d',
            'oyster_white' => '#f4f0e7',
            'warm_sand' => '#d5bea0',
            default => null,
        };
    }
};
