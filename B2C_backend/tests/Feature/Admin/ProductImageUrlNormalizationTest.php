<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ProductImageUrlNormalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('community.uploads.disk', 'public');
    }

    public function test_product_edit_form_can_save_when_existing_image_urls_contain_relative_paths(): void
    {
        $admin = User::factory()->admin()->create();
        $product = $this->productWithRequiredAdminFields();
        $variant = $product->defaultVariant();

        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'image_url' => ' /image/legacy-product.jpg ',
                'media_path' => null,
                'media_url' => null,
            ]);
        DB::table('product_variants')
            ->where('id', $variant?->id)
            ->update([
                'image_url' => 'cms/products/variants/legacy-variant.jpg',
                'media_path' => null,
            ]);

        $this->actingAs($admin);

        Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $variant?->refresh();

        $this->assertSame('image/legacy-product.jpg', $product->getRawOriginal('media_path'));
        $this->assertNull($product->getRawOriginal('image_url'));
        $this->assertSame('cms/products/variants/legacy-variant.jpg', $variant?->getRawOriginal('media_path'));
        $this->assertNull($variant?->getRawOriginal('image_url'));
    }

    public function test_relative_product_image_url_is_moved_to_media_path_and_cleared(): void
    {
        $product = $this->productWithRequiredAdminFields([
            'image_url' => ' public/image/product.jpg ',
            'media_path' => null,
            'media_url' => null,
        ]);

        $product->refresh();

        $this->assertSame('public/image/product.jpg', $product->getRawOriginal('media_path'));
        $this->assertNull($product->getRawOriginal('image_url'));
    }

    public function test_relative_variant_image_url_is_moved_to_media_path_and_cleared(): void
    {
        $variant = ProductVariant::factory()->create([
            'image_url' => ' /storage/variants/variant.jpg ',
            'media_path' => null,
        ]);

        $variant->refresh();

        $this->assertSame('storage/variants/variant.jpg', $variant->getRawOriginal('media_path'));
        $this->assertNull($variant->getRawOriginal('image_url'));
    }

    public function test_valid_https_image_urls_remain_unchanged(): void
    {
        $product = $this->productWithRequiredAdminFields([
            'image_url' => ' https://example.com/product.jpg ',
            'media_path' => null,
            'media_url' => null,
        ]);
        $variant = ProductVariant::factory()->create([
            'image_url' => ' https://example.com/variant.jpg ',
            'media_path' => null,
        ]);

        $product->refresh();
        $variant->refresh();

        $this->assertSame('https://example.com/product.jpg', $product->getRawOriginal('image_url'));
        $this->assertSame('https://example.com/variant.jpg', $variant->getRawOriginal('image_url'));
    }

    public function test_relative_product_proof_urls_are_moved_to_stored_paths_and_cleared(): void
    {
        $product = $this->productWithRequiredAdminFields([
            'certifications' => [
                [
                    'name' => 'Water absorption',
                    'status' => 'tested',
                    'document_url' => ' /docs/water-absorption.pdf ',
                ],
            ],
            'technical_downloads' => [
                [
                    'title' => 'Spec sheet',
                    'type' => 'product_specification_sheet',
                    'status' => 'available',
                    'url' => ' cms/products/downloads/spec-sheet.pdf ',
                ],
            ],
        ]);

        $product->refresh();

        $certification = $product->certifications[0] ?? [];
        $download = $product->technical_downloads[0] ?? [];

        $this->assertSame('docs/water-absorption.pdf', $certification['document_path'] ?? null);
        $this->assertNull($certification['document_url'] ?? null);
        $this->assertSame('cms/products/downloads/spec-sheet.pdf', $download['file_path'] ?? null);
        $this->assertNull($download['url'] ?? null);
    }

    public function test_external_product_proof_urls_remain_unchanged(): void
    {
        $product = $this->productWithRequiredAdminFields([
            'certifications' => [
                [
                    'name' => 'Water absorption',
                    'status' => 'tested',
                    'document_url' => ' https://example.com/water-absorption.pdf ',
                ],
            ],
            'technical_downloads' => [
                [
                    'title' => 'Spec sheet',
                    'type' => 'product_specification_sheet',
                    'status' => 'available',
                    'url' => ' https://example.com/spec-sheet.pdf ',
                ],
            ],
        ]);

        $product->refresh();

        $certification = $product->certifications[0] ?? [];
        $download = $product->technical_downloads[0] ?? [];

        $this->assertSame('https://example.com/water-absorption.pdf', $certification['document_url'] ?? null);
        $this->assertArrayNotHasKey('document_path', $certification);
        $this->assertSame('https://example.com/spec-sheet.pdf', $download['url'] ?? null);
        $this->assertArrayNotHasKey('file_path', $download);
    }

    public function test_invalid_external_url_uses_filament_validation_instead_of_native_browser_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $product = $this->productWithRequiredAdminFields();
        $variant = $product->defaultVariant();
        $variantKey = 'record-'.$variant?->id;

        $this->actingAs($admin);

        Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->assertFormFieldExists('image_url', fn (TextInput $field): bool => $field->getType() === 'text')
            ->fillForm([
                'image_url' => 'not-a-url',
                "variants.{$variantKey}.image_url" => 'also-not-a-url',
            ])
            ->call('save')
            ->assertHasFormErrors([
                'image_url' => 'url',
                "variants.{$variantKey}.image_url" => 'url',
            ]);
    }

    public function test_cleanup_migration_moves_legacy_relative_urls_without_overwriting_media_path(): void
    {
        $product = $this->productWithRequiredAdminFields();
        $variant = $product->defaultVariant();

        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'image_url' => '/image/migrated-product.jpg',
                'media_path' => null,
                'media_url' => null,
            ]);
        DB::table('product_variants')
            ->where('id', $variant?->id)
            ->update([
                'image_url' => 'public/image/migrated-variant.jpg',
                'media_path' => 'cms/products/variants/existing.jpg',
            ]);

        $migration = require database_path('migrations/2026_05_15_000000_normalize_product_image_urls.php');
        $migration->up();

        $product->refresh();
        $variant?->refresh();

        $this->assertSame('image/migrated-product.jpg', $product->getRawOriginal('media_path'));
        $this->assertNull($product->getRawOriginal('image_url'));
        $this->assertSame('cms/products/variants/existing.jpg', $variant?->getRawOriginal('media_path'));
        $this->assertNull($variant?->getRawOriginal('image_url'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function productWithRequiredAdminFields(array $attributes = []): Product
    {
        return Product::factory()->create([
            'name' => 'Admin Image Test Product',
            'name_translations' => [
                'en' => 'Admin Image Test Product',
                'ko' => 'Admin Image Test Product',
                'zh' => 'Admin Image Test Product',
            ],
            'certifications' => [],
            ...$attributes,
        ]);
    }
}
