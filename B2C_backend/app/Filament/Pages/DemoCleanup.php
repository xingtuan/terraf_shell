<?php

namespace App\Filament\Pages;

use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Models\AdminActionLog;
use App\Models\Cart;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductAttributeAssignment;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Database\Seeders\ProductCatalogSeeder;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as DbSchema;

class DemoCleanup extends Page
{
    private const SHOP_SEED_SOURCE = 'product_catalog_demo';

    public ?array $data = [];

    protected static ?string $title = null;

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::SystemSettings;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trash';

    protected static ?int $navigationSort = 100;

    protected static ?string $slug = 'demo-cleanup';

    public static function canAccess(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.pages.demo_cleanup');
    }

    public function getTitle(): string
    {
        return __('admin.pages.demo_cleanup');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make(__('admin.demo_cleanup.sections.detected'))
                ->description(__('admin.demo_cleanup.help.safe_scope'))
                ->schema([
                    Grid::make(3)->schema([
                        Placeholder::make('demo_posts')
                            ->label(__('admin.demo_cleanup.fields.demo_posts'))
                            ->content(fn (): string => (string) $this->demoPostCount()),
                        Placeholder::make('demo_comments')
                            ->label(__('admin.demo_cleanup.fields.demo_comments'))
                            ->content(fn (): string => (string) $this->demoCommentCount()),
                        Placeholder::make('demo_orders')
                            ->label(__('admin.demo_cleanup.fields.demo_orders'))
                            ->content('0'),
                        Placeholder::make('demo_leads')
                            ->label(__('admin.demo_cleanup.fields.demo_leads'))
                            ->content('0'),
                        Placeholder::make('demo_media')
                            ->label(__('admin.demo_cleanup.fields.demo_media'))
                            ->content('0'),
                        Placeholder::make('demo_users')
                            ->label(__('admin.demo_cleanup.fields.demo_users'))
                            ->content('0'),
                    ]),
                ]),
            Section::make('Demo shop catalog')
                ->description('Seeded shop products can be reseeded or removed without touching real order history.')
                ->schema([
                    Grid::make(3)->schema([
                        Placeholder::make('demo_product_categories')
                            ->label('Demo product categories')
                            ->content(fn (): string => (string) $this->demoProductCategoryCount()),
                        Placeholder::make('demo_products')
                            ->label('Demo products')
                            ->content(fn (): string => (string) $this->demoProductCount()),
                        Placeholder::make('demo_product_images')
                            ->label('Demo product images')
                            ->content(fn (): string => (string) $this->demoProductImageCount()),
                        Placeholder::make('demo_product_variants')
                            ->label('Demo product variants')
                            ->content(fn (): string => (string) $this->demoProductVariantCount()),
                        Placeholder::make('demo_product_attribute_assignments')
                            ->label('Demo product attributes')
                            ->content(fn (): string => (string) $this->demoProductAttributeAssignmentCount()),
                        Placeholder::make('affected_demo_cart_items')
                            ->label('Affected cart items')
                            ->content(fn (): string => (string) $this->affectedDemoCartItemCount()),
                        Placeholder::make('blocking_real_order_items')
                            ->label('Blocking order items')
                            ->content(fn (): string => (string) $this->blockingRealOrderItemCount()),
                        Placeholder::make('blocking_real_orders')
                            ->label('Blocking orders')
                            ->content(fn (): string => (string) $this->blockingRealOrderCount()),
                    ]),
                ]),
        ]);
    }

    public function cleanupCommunityDemoContent(): void
    {
        $postIds = $this->demoPostIds();
        $counts = [
            'posts' => count($postIds),
            'comments' => $this->demoCommentCount(),
        ];

        DB::transaction(function () use ($postIds, $counts): void {
            if ($postIds !== []) {
                Post::query()->whereKey($postIds)->delete();
            }

            $this->logAdminAction(
                'demo_cleanup.community',
                __('admin.ui.cleaned_marked_demo_community_content'),
                $counts,
            );
        });

        Notification::make()
            ->title(__('admin.demo_cleanup.messages.cleaned'))
            ->success()
            ->send();
    }

    public function cleanupShopCatalogDemoContent(): void
    {
        $result = DB::transaction(function (): array {
            $productIds = $this->demoProductIds();
            $blockedProductIds = $this->blockingOrderProductIds($productIds);
            $deletableProductIds = array_values(array_diff($productIds, $blockedProductIds));
            $cartIds = $deletableProductIds === []
                ? []
                : DB::table('cart_items')
                    ->whereIn('product_id', $deletableProductIds)
                    ->pluck('cart_id')
                    ->unique()
                    ->map(fn ($id): int => (int) $id)
                    ->all();

            $counts = [
                'detected_products' => count($productIds),
                'deleted_products' => count($deletableProductIds),
                'blocked_products' => count($blockedProductIds),
                'blocking_orders' => $this->blockingRealOrderCount(),
                'blocking_order_items' => $this->blockingRealOrderItemCount(),
                'deleted_cart_items' => $deletableProductIds === []
                    ? 0
                    : (int) DB::table('cart_items')->whereIn('product_id', $deletableProductIds)->delete(),
                'deleted_related_product_rows' => $deletableProductIds === []
                    ? 0
                    : (int) DB::table('product_related_products')
                        ->whereIn('product_id', $deletableProductIds)
                        ->orWhereIn('related_product_id', $deletableProductIds)
                        ->delete(),
                'deleted_attribute_assignments' => $deletableProductIds === []
                    ? 0
                    : ProductAttributeAssignment::query()->whereIn('product_id', $deletableProductIds)->delete(),
                'deleted_images' => $deletableProductIds === []
                    ? 0
                    : ProductImage::query()->whereIn('product_id', $deletableProductIds)->delete(),
                'deleted_variants' => $deletableProductIds === []
                    ? 0
                    : ProductVariant::query()->whereIn('product_id', $deletableProductIds)->delete(),
            ];

            if ($deletableProductIds !== []) {
                Product::query()->whereKey($deletableProductIds)->delete();
            }

            $counts['deleted_empty_carts'] = $cartIds === []
                ? 0
                : Cart::query()->whereKey($cartIds)->whereDoesntHave('items')->delete();

            $counts['deleted_categories'] = ProductCategory::query()
                ->whereKey($this->demoProductCategoryIds())
                ->whereDoesntHave('products')
                ->delete();

            $this->logAdminAction(
                'demo_cleanup.shop_catalog',
                'Cleaned demo shop catalog content.',
                $counts,
            );

            return $counts;
        });

        $notification = Notification::make()
            ->title('Demo shop catalog cleanup completed');

        if (($result['blocked_products'] ?? 0) > 0) {
            $notification
                ->warning()
                ->body("{$result['blocked_products']} demo products are still referenced by {$result['blocking_orders']} orders and were not deleted.");
        } else {
            $notification->success();
        }

        $notification->send();
    }

    public function seedShopCatalogDemoContent(): void
    {
        Artisan::call('db:seed', [
            '--class' => ProductCatalogSeeder::class,
            '--force' => true,
        ]);

        $this->logAdminAction(
            'demo_cleanup.shop_catalog_seed',
            'Seeded demo shop catalog content.',
            ['seed_source' => self::SHOP_SEED_SOURCE],
        );

        Notification::make()
            ->title('Demo shop catalog seeded')
            ->success()
            ->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->footer([Actions::make([
                    Action::make('cleanupCommunityDemoContent')
                        ->label(__('admin.demo_cleanup.actions.cleanup_community'))
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action('cleanupCommunityDemoContent'),
                    Action::make('seedShopCatalogDemoContent')
                        ->label('Seed demo shop catalog')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action('seedShopCatalogDemoContent'),
                    Action::make('cleanupShopCatalogDemoContent')
                        ->label('Cleanup demo shop catalog')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action('cleanupShopCatalogDemoContent'),
                ])]),
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function demoPostIds(): array
    {
        return DbSchema::hasColumn('posts', 'is_demo_content')
            ? Post::query()->where('is_demo_content', true)->pluck('id')->map(fn ($id): int => (int) $id)->all()
            : [];
    }

    private function demoPostCount(): int
    {
        return count($this->demoPostIds());
    }

    private function demoCommentCount(): int
    {
        $ids = $this->demoPostIds();

        return $ids === [] ? 0 : (int) DB::table('comments')->whereIn('post_id', $ids)->count();
    }

    /**
     * @return array<int, int>
     */
    private function demoProductIds(): array
    {
        return Product::query()
            ->where(fn ($query) => $query
                ->where('is_demo_content', true)
                ->orWhere('seed_source', self::SHOP_SEED_SOURCE))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function demoProductCategoryIds(): array
    {
        return ProductCategory::query()
            ->where(fn ($query) => $query
                ->where('is_demo_content', true)
                ->orWhere('seed_source', self::SHOP_SEED_SOURCE))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function demoProductCount(): int
    {
        return count($this->demoProductIds());
    }

    private function demoProductCategoryCount(): int
    {
        return count($this->demoProductCategoryIds());
    }

    private function demoProductImageCount(): int
    {
        $productIds = $this->demoProductIds();

        return (int) ProductImage::query()
            ->where(fn ($query) => $query
                ->where('is_demo_content', true)
                ->orWhere('seed_source', self::SHOP_SEED_SOURCE)
                ->when($productIds !== [], fn ($query) => $query->orWhereIn('product_id', $productIds)))
            ->count();
    }

    private function demoProductVariantCount(): int
    {
        $productIds = $this->demoProductIds();

        return (int) ProductVariant::query()
            ->where(fn ($query) => $query
                ->where('is_demo_content', true)
                ->orWhere('seed_source', self::SHOP_SEED_SOURCE)
                ->when($productIds !== [], fn ($query) => $query->orWhereIn('product_id', $productIds)))
            ->count();
    }

    private function demoProductAttributeAssignmentCount(): int
    {
        $productIds = $this->demoProductIds();

        return (int) ProductAttributeAssignment::query()
            ->where(fn ($query) => $query
                ->where('is_demo_content', true)
                ->orWhere('seed_source', self::SHOP_SEED_SOURCE)
                ->when($productIds !== [], fn ($query) => $query->orWhereIn('product_id', $productIds)))
            ->count();
    }

    private function affectedDemoCartItemCount(): int
    {
        $productIds = $this->demoProductIds();

        return $productIds === [] ? 0 : (int) DB::table('cart_items')->whereIn('product_id', $productIds)->count();
    }

    private function blockingRealOrderItemCount(): int
    {
        $productIds = $this->demoProductIds();

        return $productIds === [] ? 0 : (int) DB::table('order_items')->whereIn('product_id', $productIds)->count();
    }

    private function blockingRealOrderCount(): int
    {
        $productIds = $this->demoProductIds();

        return $productIds === [] ? 0 : (int) DB::table('order_items')
            ->whereIn('product_id', $productIds)
            ->distinct()
            ->count('order_id');
    }

    /**
     * @param  array<int, int>  $productIds
     * @return array<int, int>
     */
    private function blockingOrderProductIds(array $productIds): array
    {
        return $productIds === []
            ? []
            : DB::table('order_items')
                ->whereIn('product_id', $productIds)
                ->pluck('product_id')
                ->unique()
                ->map(fn ($id): int => (int) $id)
                ->all();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function logAdminAction(string $action, string $description, array $metadata): void
    {
        if (! DbSchema::hasTable('admin_action_logs')) {
            return;
        }

        AdminActionLog::query()->create([
            'actor_user_id' => PanelAccess::user()?->id,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
