<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = [
        'product_categories',
        'products',
        'product_images',
        'product_variants',
        'product_attribute_assignments',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'is_demo_content')) {
                    $table->boolean('is_demo_content')->default(false)->index();
                }

                if (! Schema::hasColumn($tableName, 'seed_source')) {
                    $table->string('seed_source')->nullable()->index();
                }

                if (! Schema::hasColumn($tableName, 'seeded_at')) {
                    $table->timestamp('seeded_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $columns = collect([
                    'is_demo_content',
                    'seed_source',
                    'seeded_at',
                ])
                    ->filter(fn (string $column): bool => Schema::hasColumn($tableName, $column))
                    ->values()
                    ->all();

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
