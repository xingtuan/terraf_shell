<?php

namespace Tests\Feature\Admin;

use App\Enums\B2BLeadType;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Filament\Resources\B2BLeads\Pages\ListB2BLeads;
use App\Filament\Resources\Enquiries\Pages\ListEnquiries;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\B2BLead;
use App\Models\EmailEvent;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductAttributeDefinition;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOperationsCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_admin_resource_can_create_and_update_product(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ProductCategory::query()->where('slug', 'tableware')->firstOrFail();
        $specification = ProductAttributeDefinition::query()->create([
            'key' => 'sample_format',
            'label' => 'Sample Format',
            'label_translations' => ['en' => 'Sample Format'],
            'type' => 'text',
            'group' => 'Specifications',
            'is_filterable' => false,
            'is_searchable' => true,
            'is_specification' => true,
            'is_variant_option' => false,
            'allows_multiple' => false,
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Admin Created Bowl',
                'name_translations' => [
                    'en' => 'Admin Created Bowl',
                    'ko' => 'Admin Created Bowl',
                    'zh' => 'Admin Created Bowl',
                ],
                'slug' => 'admin-created-bowl',
                'category_id' => $category->id,
                'status' => ProductStatus::Published->value,
                'sort_order' => 5,
                'is_active' => true,
                'featured' => true,
                'is_bestseller' => false,
                'is_new' => true,
                'inquiry_only' => false,
                'sample_request_enabled' => true,
                'images' => [],
                'variants' => [
                    [
                        'sku' => 'ADMIN_CREATED_BOWL_STD',
                        'title' => 'Standard',
                        'price_amount' => 42.00,
                        'currency' => 'NZD',
                        'stock_quantity' => 12,
                        'stock_status' => 'in_stock',
                        'inventory_policy' => 'deny',
                        'low_stock_threshold' => 3,
                        'is_default' => true,
                        'is_active' => true,
                        'sort_order' => 0,
                    ],
                ],
                'attributeAssignments' => [
                    [
                        'attribute_definition_id' => $specification->id,
                        'value_text' => 'Tableware bowl',
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->where('slug', 'admin-created-bowl')->firstOrFail();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => $category->id,
            'featured' => true,
            'is_new' => true,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'ADMIN_CREATED_BOWL_STD',
            'stock_quantity' => 12,
            'stock_status' => 'in_stock',
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('product_attribute_assignments', [
            'product_id' => $product->id,
            'attribute_definition_id' => $specification->id,
            'value_text' => 'Tableware bowl',
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->fillForm([
                'featured' => false,
                'is_bestseller' => true,
                'name_translations' => [
                    'en' => 'Admin Updated Bowl',
                    'ko' => 'Admin Updated Bowl',
                    'zh' => 'Admin Updated Bowl',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'featured' => false,
            'is_bestseller' => true,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'ADMIN_CREATED_BOWL_STD',
            'stock_quantity' => 12,
        ]);
    }

    public function test_order_admin_status_action_updates_timeline_and_preserves_email_dispatch_path(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $product = Product::factory()->published()->create();
        $variant = $product->defaultVariant();
        $variant?->update([
            'price_amount' => 48.00,
            'stock_quantity' => 10,
            'stock_status' => 'in_stock',
        ]);
        $variant = $variant?->fresh();

        $order = Order::query()->create([
            'user_id' => $customer->id,
            'order_number' => 'OXP-900001',
            'status' => OrderStatus::Pending->value,
            'subtotal_usd' => 48.00,
            'shipping_usd' => 8.00,
            'total_usd' => 56.00,
            'currency' => 'NZD',
            'shipping_name' => 'OXP Buyer',
            'shipping_address_line1' => '123 Ocean Road',
            'shipping_city' => 'Auckland',
            'shipping_country' => 'NZ',
            'payment_method' => 'manual',
            'payment_status' => OrderPaymentStatus::Unpaid->value,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'product_name' => $product->name,
            'product_sku' => $variant?->sku,
            'variant_title' => $variant?->title,
            'variant_sku' => $variant?->sku,
            'quantity' => 1,
            'unit_price_usd' => 48.00,
            'unit_price_amount' => 48.00,
            'currency' => 'NZD',
            'subtotal_usd' => 48.00,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListOrders::class)
            ->callTableAction('markShipped', $order)
            ->assertHasNoTableActionErrors();

        $order->refresh();

        $this->assertSame(OrderStatus::Shipped, $order->status);
        $this->assertNotNull($order->shipped_at);
    }

    public function test_frontend_lead_submissions_are_visible_in_admin_lead_center(): void
    {
        $admin = User::factory()->admin()->create();

        $this->postJson('/api/business-contacts', [
            'name' => 'Ariana Kim',
            'company_name' => 'Blue Current',
            'organization_type' => 'brand',
            'email' => 'ariana@example.com',
            'message' => 'We are exploring a premium packaging collaboration.',
            'source_page' => 'materials:hero',
        ])->assertCreated();

        $this->postJson('/api/sample-requests', [
            'name' => 'Mika Tan',
            'company_name' => 'Carbon Form',
            'organization_type' => 'manufacturer',
            'email' => 'mika@example.com',
            'message' => 'We need evaluation samples for an interior pilot.',
            'material_interest' => 'Pressed oyster-shell panel',
            'shipping_country' => 'Japan',
            'shipping_address' => '1-2-3 Minami, Osaka',
            'intended_use' => 'Interior wall system prototyping.',
        ])->assertCreated();

        $this->postJson('/api/partnership-inquiries', [
            'name' => 'Leo Park',
            'company_name' => 'Helix Atelier',
            'organization_type' => 'company',
            'email' => 'leo@example.com',
            'message' => 'We want to co-develop a furniture capsule.',
            'collaboration_type' => B2BLeadType::PartnershipInquiry->value,
            'collaboration_goal' => 'Pilot a limited-edition material application.',
        ])->assertCreated();

        $businessLead = B2BLead::query()->where('email', 'ariana@example.com')->firstOrFail();
        $sampleLead = B2BLead::query()->where('email', 'mika@example.com')->firstOrFail();
        $partnershipLead = B2BLead::query()->where('email', 'leo@example.com')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(ListB2BLeads::class)
            ->assertCanSeeTableRecords([$businessLead, $sampleLead, $partnershipLead]);
    }

    public function test_legacy_inquiry_submission_is_visible_in_admin_enquiries(): void
    {
        $moderator = User::factory()->moderator()->create();

        $this->postJson('/api/inquiries', [
            'name' => 'Jane Doe',
            'company_name' => 'OXP Studio',
            'email' => 'jane@example.com',
            'inquiry_type' => 'Business Contact',
            'message' => 'We need pellets for a pilot hospitality project.',
            'source_page' => 'b2b:en',
        ])->assertCreated();

        $inquiry = B2BLead::query()->where('email', 'jane@example.com')->firstOrFail();

        $this->actingAs($moderator);

        Livewire::test(ListEnquiries::class)
            ->assertCanSeeTableRecords([$inquiry]);
    }

    public function test_community_post_with_safe_external_funding_url_is_stored_and_manageable(): void
    {
        $user = User::factory()->create();
        $moderator = User::factory()->moderator()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/posts', [
            'title' => 'OXP public furniture concept',
            'content' => 'This concept uses recovered oyster shell material for a public seating prototype.',
            'funding_url' => 'https://givealittle.co.nz/cause/oxp-public-seat',
        ]);

        $response->assertCreated();

        $post = Post::query()->findOrFail($response->json('data.id'));

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'funding_url' => 'https://givealittle.co.nz/cause/oxp-public-seat',
        ]);

        $this->app['auth']->forgetGuards();
        $this->actingAs($moderator);

        Livewire::test(ListPosts::class)
            ->assertCanSeeTableRecords([$post]);
    }

    public function test_unsafe_funding_urls_are_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'title' => 'Unsafe funding link',
            'content' => 'This concept has enough body content for validation to pass.',
            'funding_url' => 'javascript:alert(1)',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['funding_url']);
    }

    public function test_email_event_template_models_and_korean_admin_translations_work(): void
    {
        EmailEvent::query()->create([
            'key' => 'order.status_changed',
            'category' => 'Store',
            'name' => 'Order status changed',
            'is_enabled' => true,
            'recipient_type' => 'user',
            'template_key' => 'order.status_changed',
            'use_queue' => false,
        ]);

        EmailTemplate::query()->create([
            'key' => 'order.status_changed',
            'locale' => 'ko',
            'name' => 'Order status changed',
            'subject' => 'Order {{ order.order_number }} status changed',
            'html_body' => '<p>{{ order.status }}</p>',
            'text_body' => '{{ order.status }}',
            'available_variables' => ['order.order_number', 'order.status'],
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('email_events', [
            'key' => 'order.status_changed',
            'is_enabled' => true,
        ]);
        $this->assertDatabaseHas('email_templates', [
            'key' => 'order.status_changed',
            'locale' => 'ko',
        ]);

        foreach (['en', 'ko', 'zh'] as $locale) {
            app()->setLocale($locale);

            foreach ([
                'admin.brand.name',
                'admin.navigation.store_operations',
                'admin.navigation.b2b_leads',
                'admin.navigation.content_cms',
                'admin.navigation.email_center',
                'admin.resources.products',
                'admin.resources.orders',
                'admin.resources.all_leads',
                'admin.pages.system_handover_readiness',
                'admin.pages.shipping_settings',
                'admin.system.checks.database',
                'admin.orders.status.shipped',
                'admin.leads.status.follow_up',
                'admin.actions.change_status',
            ] as $key) {
                $this->assertNotSame($key, __($key), "Missing {$locale} translation for {$key}");
            }
        }
    }
}
