<?php

namespace App\Services;

use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use App\Enums\ContentStatus;
use App\Enums\FundingCampaignStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\PublishStatus;
use App\Enums\ReportStatus;
use App\Filament\Pages\ModerationQueue;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\B2BLeads\B2BLeadResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Filament\Resources\FundingCampaigns\FundingCampaignResource;
use App\Filament\Resources\HomeSections\HomeSectionResource;
use App\Filament\Resources\Materials\MaterialResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\Article;
use App\Models\B2BLead;
use App\Models\Comment;
use App\Models\FundingCampaign;
use App\Models\HomeSection;
use App\Models\Material;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\Report;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardInsightsService
{
    public function snapshot(bool $includeAdmin = true): array
    {
        $moderation = $this->moderationMetrics();
        $community = $this->communityMetrics();

        if (! $includeAdmin) {
            return [
                'generated_at' => now()->toISOString(),
                'moderation' => $moderation,
                'community' => $community,
                'activity' => $this->staffActivity(),
            ];
        }

        $commerce = $this->commerceMetrics();
        $leads = $this->leadMetrics();
        $content = $this->contentMetrics();
        $growth = $this->growthMetrics();

        return [
            'generated_at' => now()->toISOString(),
            'hero' => $this->heroSummary($commerce, $leads, $moderation, $content, $community, $growth),
            'kpis' => $this->kpiSummary($commerce, $leads, $moderation),
            'commerce' => $commerce,
            'leads' => $leads,
            'moderation' => $moderation,
            'content' => $content,
            'community' => $community,
            'growth' => $growth,
            'activity' => $this->adminActivity(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function commerceMetrics(): array
    {
        $backlogStatuses = [
            OrderStatus::Pending->value,
            OrderStatus::Confirmed->value,
            OrderStatus::Processing->value,
            OrderStatus::Shipped->value,
        ];

        $orderWindow = $this->dateWindow(30);
        $orderRows = Order::query()
            ->whereBetween('created_at', [$orderWindow['start'], $orderWindow['end']])
            ->get(['id', 'status', 'total_usd', 'payment_status', 'created_at']);

        $orderCountSeries = $this->sumByDay(
            $orderRows,
            $orderWindow['days'],
            fn (Order $order): int => 1,
        );

        $revenueSeries = $this->sumByDay(
            $orderRows->where('status', '!=', OrderStatus::Cancelled->value),
            $orderWindow['days'],
            fn (Order $order): float => (float) $order->total_usd,
        );

        $productWindow = $this->dateWindow(30, 'published_at');
        $productRows = Product::query()
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$productWindow['start'], $productWindow['end']])
            ->get(['id', 'published_at']);

        return [
            'backlog_count' => Order::query()->whereIn('status', $backlogStatuses)->count(),
            'unpaid_count' => Order::query()
                ->where('status', '!=', OrderStatus::Cancelled->value)
                ->where('payment_status', 'unpaid')
                ->count(),
            'revenue_30d' => $this->sumForPeriod(Order::query(), 30, 'total_usd', [
                ['status', '!=', OrderStatus::Cancelled->value],
            ]),
            'revenue_previous_30d' => $this->sumForPeriod(Order::query(), 30, 'total_usd', [
                ['status', '!=', OrderStatus::Cancelled->value],
            ], previous: true),
            'orders_last_14d' => $this->countForPeriod(Order::query(), 14),
            'published_products' => Product::query()
                ->where('status', ProductStatus::Published->value)
                ->where('is_active', true)
                ->count(),
            'products_launched_30d' => $this->countForPeriod(Product::query()->whereNotNull('published_at'), 30, 'published_at'),
            'low_stock_count' => Product::query()
                ->where('status', ProductStatus::Published->value)
                ->whereIn('stock_status', ['low_stock', 'sold_out'])
                ->count(),
            'sold_out_count' => Product::query()
                ->where('status', ProductStatus::Published->value)
                ->where('stock_status', 'sold_out')
                ->count(),
            'labels_30d' => $this->labelsForDays($orderWindow['days']),
            'orders_series_30d' => array_values($orderCountSeries),
            'revenue_series_30d' => array_map(
                fn (float $value): float => round($value, 2),
                array_values($revenueSeries),
            ),
            'product_launch_series_30d' => array_values(
                $this->sumByDay($productRows, $productWindow['days'], fn (): int => 1),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function leadMetrics(): array
    {
        $window = $this->dateWindow(30);
        $openStatuses = [
            B2BLeadStatus::New->value,
            B2BLeadStatus::InReview->value,
            B2BLeadStatus::Contacted->value,
            B2BLeadStatus::Qualified->value,
        ];

        $leadRows = B2BLead::query()
            ->whereBetween('created_at', [$window['start'], $window['end']])
            ->get(['id', 'lead_type', 'status', 'assigned_to', 'created_at']);

        $enquirySeries = $this->sumByDay(
            $leadRows->where('lead_type', B2BLeadType::BusinessContact->value),
            $window['days'],
            fn (): int => 1,
        );

        $opportunitySeries = $this->sumByDay(
            $leadRows->where('lead_type', '!=', B2BLeadType::BusinessContact->value),
            $window['days'],
            fn (): int => 1,
        );

        $qualifiedSeries = $this->sumByDay(
            $leadRows->where('status', B2BLeadStatus::Qualified->value),
            $window['days'],
            fn (): int => 1,
        );

        $openEnquiriesQuery = B2BLead::query()
            ->where('lead_type', B2BLeadType::BusinessContact->value)
            ->whereIn('status', B2BLeadStatus::enquiryValues());

        return [
            'open_enquiries' => (clone $openEnquiriesQuery)
                ->whereNotIn('status', [B2BLeadStatus::Archived->value, B2BLeadStatus::Closed->value])
                ->count(),
            'unassigned_enquiries' => (clone $openEnquiriesQuery)
                ->whereNotIn('status', [B2BLeadStatus::Archived->value, B2BLeadStatus::Closed->value])
                ->whereNull('assigned_to')
                ->count(),
            'open_opportunities' => B2BLead::query()
                ->where('lead_type', '!=', B2BLeadType::BusinessContact->value)
                ->whereIn('status', $openStatuses)
                ->count(),
            'qualified_leads' => B2BLead::query()
                ->where('status', B2BLeadStatus::Qualified->value)
                ->count(),
            'new_submissions_14d' => $this->countForPeriod(B2BLead::query(), 14),
            'labels_30d' => $this->labelsForDays($window['days']),
            'enquiries_series_30d' => array_values($enquirySeries),
            'opportunities_series_30d' => array_values($opportunitySeries),
            'qualified_series_30d' => array_values($qualifiedSeries),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function moderationMetrics(): array
    {
        $window = $this->dateWindow(14);

        $pendingPosts = Post::query()
            ->where('status', ContentStatus::Pending->value)
            ->whereBetween('created_at', [$window['start'], $window['end']])
            ->get(['id', 'created_at']);

        $pendingComments = Comment::query()
            ->where('status', ContentStatus::Pending->value)
            ->whereBetween('created_at', [$window['start'], $window['end']])
            ->get(['id', 'created_at']);

        $openReports = Report::query()
            ->where('status', ReportStatus::Pending->value)
            ->whereBetween('created_at', [$window['start'], $window['end']])
            ->get(['id', 'created_at']);

        return [
            'pending_posts' => Post::query()->where('status', ContentStatus::Pending->value)->count(),
            'pending_comments' => Comment::query()->where('status', ContentStatus::Pending->value)->count(),
            'open_reports' => Report::query()->where('status', ReportStatus::Pending->value)->count(),
            'backlog_total' => Post::query()->where('status', ContentStatus::Pending->value)->count()
                + Comment::query()->where('status', ContentStatus::Pending->value)->count()
                + Report::query()->where('status', ReportStatus::Pending->value)->count(),
            'labels_14d' => $this->labelsForDays($window['days']),
            'pending_posts_series_14d' => array_values($this->sumByDay($pendingPosts, $window['days'], fn (): int => 1)),
            'pending_comments_series_14d' => array_values($this->sumByDay($pendingComments, $window['days'], fn (): int => 1)),
            'open_reports_series_14d' => array_values($this->sumByDay($openReports, $window['days'], fn (): int => 1)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function contentMetrics(): array
    {
        $window = $this->dateWindow(30, 'published_at');

        $materials = Material::query()
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$window['start'], $window['end']])
            ->get(['id', 'published_at']);
        $articles = Article::query()
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$window['start'], $window['end']])
            ->get(['id', 'published_at']);
        $sections = HomeSection::query()
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$window['start'], $window['end']])
            ->get(['id', 'published_at']);

        $publishedMaterials = Material::query()->where('status', PublishStatus::Published->value)->count();
        $publishedArticles = Article::query()->where('status', PublishStatus::Published->value)->count();
        $publishedSections = HomeSection::query()->where('status', PublishStatus::Published->value)->count();

        $draftMaterials = Material::query()->where('status', PublishStatus::Draft->value)->count();
        $draftArticles = Article::query()->where('status', PublishStatus::Draft->value)->count();
        $draftSections = HomeSection::query()->where('status', PublishStatus::Draft->value)->count();

        return [
            'published_materials' => $publishedMaterials,
            'published_articles' => $publishedArticles,
            'published_sections' => $publishedSections,
            'published_total' => $publishedMaterials + $publishedArticles + $publishedSections,
            'draft_total' => $draftMaterials + $draftArticles + $draftSections,
            'published_this_month' => $this->countForPeriod(Material::query()->whereNotNull('published_at'), 30, 'published_at')
                + $this->countForPeriod(Article::query()->whereNotNull('published_at'), 30, 'published_at')
                + $this->countForPeriod(HomeSection::query()->whereNotNull('published_at'), 30, 'published_at'),
            'labels_30d' => $this->labelsForDays($window['days']),
            'materials_series_30d' => array_values($this->sumByDay($materials, $window['days'], fn (): int => 1, 'published_at')),
            'articles_series_30d' => array_values($this->sumByDay($articles, $window['days'], fn (): int => 1, 'published_at')),
            'sections_series_30d' => array_values($this->sumByDay($sections, $window['days'], fn (): int => 1, 'published_at')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function communityMetrics(): array
    {
        $rows = DB::table('posts')
            ->join('categories', 'categories.id', '=', 'posts.category_id')
            ->where('posts.status', ContentStatus::Approved->value)
            ->select('categories.id', 'categories.name')
            ->selectRaw('COUNT(posts.id) as concept_count')
            ->selectRaw('COALESCE(SUM(posts.engagement_score), 0) as engagement_total')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('concept_count')
            ->orderByDesc('engagement_total')
            ->limit(6)
            ->get();

        $topCategory = $rows->first();

        return [
            'labels' => $rows->pluck('name')->all(),
            'concepts' => $rows->pluck('concept_count')->map(fn (mixed $value): int => (int) $value)->all(),
            'engagement' => $rows->pluck('engagement_total')->map(fn (mixed $value): int => (int) $value)->all(),
            'top_category' => [
                'name' => $topCategory?->name ?? 'No category data',
                'concept_count' => (int) ($topCategory->concept_count ?? 0),
                'engagement_total' => (int) ($topCategory->engagement_total ?? 0),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function growthMetrics(): array
    {
        $topCta = DB::table('inquiries')
            ->whereNotNull('source_page')
            ->where('source_page', '!=', '')
            ->select('source_page')
            ->selectRaw('COUNT(*) as lead_count')
            ->selectRaw(
                'SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as high_intent_count',
                [B2BLeadStatus::Qualified->value, B2BLeadStatus::Closed->value],
            )
            ->groupBy('source_page')
            ->orderByDesc('lead_count')
            ->first();

        return [
            'top_cta_source' => [
                'label' => $topCta?->source_page ?? 'No tracked source yet',
                'lead_count' => (int) ($topCta->lead_count ?? 0),
                'high_intent_rate' => isset($topCta->lead_count) && ((int) $topCta->lead_count > 0)
                    ? round((((int) $topCta->high_intent_count) / (int) $topCta->lead_count) * 100, 1)
                    : 0.0,
            ],
            'campaigns' => [
                'live' => FundingCampaign::query()->where('campaign_status', FundingCampaignStatus::Live->value)->count(),
                'scheduled' => FundingCampaign::query()->where('campaign_status', FundingCampaignStatus::Scheduled->value)->count(),
                'support_enabled' => FundingCampaign::query()->where('support_enabled', true)->count(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $commerce
     * @param  array<string, mixed>  $leads
     * @param  array<string, mixed>  $moderation
     * @return array<string, mixed>
     */
    private function kpiSummary(array $commerce, array $leads, array $moderation): array
    {
        return [
            'open_orders' => [
                'value' => $commerce['backlog_count'],
                'recent' => $this->countForPeriod(Order::query(), 14),
                'chart' => array_slice($commerce['orders_series_30d'], -14),
            ],
            'revenue' => [
                'value' => $commerce['revenue_30d'],
                'previous' => $commerce['revenue_previous_30d'],
                'chart' => $commerce['revenue_series_30d'],
            ],
            'open_leads' => [
                'value' => $leads['open_enquiries'] + $leads['open_opportunities'],
                'recent' => $leads['new_submissions_14d'],
                'chart' => array_slice(
                    array_map(
                        fn (int $enquiries, int $opportunities): int => $enquiries + $opportunities,
                        $leads['enquiries_series_30d'],
                        $leads['opportunities_series_30d'],
                    ),
                    -14,
                ),
            ],
            'moderation_backlog' => [
                'value' => $moderation['backlog_total'],
                'recent' => array_sum($moderation['pending_posts_series_14d'])
                    + array_sum($moderation['pending_comments_series_14d'])
                    + array_sum($moderation['open_reports_series_14d']),
                'chart' => array_map(
                    fn (int $posts, int $comments, int $reports): int => $posts + $comments + $reports,
                    $moderation['pending_posts_series_14d'],
                    $moderation['pending_comments_series_14d'],
                    $moderation['open_reports_series_14d'],
                ),
            ],
            'published_products' => [
                'value' => $commerce['published_products'],
                'recent' => $commerce['products_launched_30d'],
                'chart' => $commerce['product_launch_series_30d'],
            ],
            'stock_alerts' => [
                'value' => $commerce['low_stock_count'],
                'sold_out' => $commerce['sold_out_count'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $commerce
     * @param  array<string, mixed>  $leads
     * @param  array<string, mixed>  $moderation
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $community
     * @param  array<string, mixed>  $growth
     * @return array<string, mixed>
     */
    private function heroSummary(
        array $commerce,
        array $leads,
        array $moderation,
        array $content,
        array $community,
        array $growth,
    ): array {
        return [
            'focus' => [
                [
                    'label' => 'Open orders',
                    'value' => $commerce['backlog_count'],
                    'tone' => $commerce['backlog_count'] > 0 ? 'warning' : 'success',
                    'url' => OrderResource::getUrl(),
                ],
                [
                    'label' => 'Lead backlog',
                    'value' => $leads['open_enquiries'] + $leads['open_opportunities'],
                    'tone' => ($leads['open_enquiries'] + $leads['open_opportunities']) > 0 ? 'warning' : 'success',
                    'url' => EnquiryResource::getUrl(),
                ],
                [
                    'label' => 'Moderation queue',
                    'value' => $moderation['backlog_total'],
                    'tone' => $moderation['backlog_total'] > 0 ? 'danger' : 'success',
                    'url' => ModerationQueue::getUrl(),
                ],
                [
                    'label' => 'Low stock',
                    'value' => $commerce['low_stock_count'],
                    'tone' => $commerce['low_stock_count'] > 0 ? 'danger' : 'success',
                    'url' => ProductResource::getUrl(),
                ],
            ],
            'insights' => [
                [
                    'label' => 'Top CTA source',
                    'value' => $growth['top_cta_source']['label'],
                    'meta' => number_format($growth['top_cta_source']['lead_count']).' leads · '.number_format($growth['top_cta_source']['high_intent_rate'], 1).'% high intent',
                    'url' => B2BLeadResource::getUrl(),
                ],
                [
                    'label' => 'Top concept category',
                    'value' => $community['top_category']['name'],
                    'meta' => number_format($community['top_category']['concept_count']).' approved concepts · '.number_format($community['top_category']['engagement_total']).' engagement',
                    'url' => CategoryResource::getUrl(),
                ],
                [
                    'label' => 'Campaign pulse',
                    'value' => number_format($growth['campaigns']['live']).' live / '.number_format($growth['campaigns']['scheduled']).' scheduled',
                    'meta' => number_format($growth['campaigns']['support_enabled']).' support-enabled concepts',
                    'url' => FundingCampaignResource::getUrl(),
                ],
                [
                    'label' => 'Publishing this month',
                    'value' => number_format($content['published_this_month']).' live updates',
                    'meta' => number_format($content['draft_total']).' draft items still in progress',
                    'url' => MaterialResource::getUrl(),
                ],
            ],
            'quick_links' => [
                [
                    'label' => 'Review orders',
                    'description' => 'Jump straight into fulfilment.',
                    'icon' => 'heroicon-o-shopping-bag',
                    'url' => OrderResource::getUrl(),
                ],
                [
                    'label' => 'Triage leads',
                    'description' => 'Open enquiries and B2B submissions.',
                    'icon' => 'heroicon-o-briefcase',
                    'url' => EnquiryResource::getUrl(),
                ],
                [
                    'label' => 'Moderate community',
                    'description' => 'Pending concepts, comments, and reports.',
                    'icon' => 'heroicon-o-shield-check',
                    'url' => ModerationQueue::getUrl(),
                ],
                [
                    'label' => 'Publish content',
                    'description' => 'Materials, articles, and homepage sections.',
                    'icon' => 'heroicon-o-sparkles',
                    'url' => MaterialResource::getUrl(),
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function adminActivity(): array
    {
        $orders = Order::query()
            ->with('user')
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (Order $order): array => [
                'scope' => 'admin',
                'timestamp' => $order->created_at?->toISOString(),
                'icon' => 'heroicon-o-shopping-bag',
                'tone' => 'warning',
                'type' => 'Order',
                'title' => $order->order_number,
                'subtitle' => collect([
                    $order->user?->name ?: 'Guest checkout',
                    '$'.number_format((float) $order->total_usd, 2),
                    ucfirst((string) $order->status->value),
                ])->implode(' · '),
                'url' => OrderResource::getUrl('view', ['record' => $order]),
            ]);

        $leads = B2BLead::query()
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (B2BLead $lead): array => [
                'scope' => 'admin',
                'timestamp' => $lead->created_at?->toISOString(),
                'icon' => $lead->lead_type === B2BLeadType::BusinessContact->value ? 'heroicon-o-inbox-stack' : 'heroicon-o-briefcase',
                'tone' => $lead->status === B2BLeadStatus::Qualified->value ? 'success' : 'info',
                'type' => $lead->lead_type === B2BLeadType::BusinessContact->value ? 'Enquiry' : 'Lead',
                'title' => $lead->reference ?: sprintf('INQ-%06d', $lead->id),
                'subtitle' => collect([
                    $lead->name,
                    $lead->company_name,
                    B2BLeadType::tryFrom($lead->lead_type)?->label() ?? $lead->lead_type,
                ])->filter()->implode(' · '),
                'url' => $lead->lead_type === B2BLeadType::BusinessContact->value
                    ? EnquiryResource::getUrl('view', ['record' => $lead->id])
                    : B2BLeadResource::getUrl('view', ['record' => $lead]),
            ]);

        $reports = Report::query()
            ->with(['reporter'])
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Report $report): array => [
                'scope' => 'staff',
                'timestamp' => $report->created_at?->toISOString(),
                'icon' => 'heroicon-o-flag',
                'tone' => 'danger',
                'type' => 'Report',
                'title' => 'Report #'.$report->id,
                'subtitle' => collect([
                    $report->reporter?->name,
                    ucfirst(class_basename((string) $report->target_type)),
                    $report->reason,
                ])->filter()->implode(' · '),
                'url' => ReportResource::getUrl('view', ['record' => $report]),
            ]);

        $content = collect([
            ...Material::query()
                ->where('status', PublishStatus::Published->value)
                ->whereNotNull('published_at')
                ->latest('published_at')
                ->limit(2)
                ->get()
                ->map(fn (Material $material): array => [
                    'scope' => 'admin',
                    'timestamp' => $material->published_at?->toISOString(),
                    'icon' => 'heroicon-o-sparkles',
                    'tone' => 'success',
                    'type' => 'Material',
                    'title' => $material->title,
                    'subtitle' => 'Published material story',
                    'url' => MaterialResource::getUrl('view', ['record' => $material]),
                ])
                ->all(),
            ...Article::query()
                ->where('status', PublishStatus::Published->value)
                ->whereNotNull('published_at')
                ->latest('published_at')
                ->limit(2)
                ->get()
                ->map(fn (Article $article): array => [
                    'scope' => 'admin',
                    'timestamp' => $article->published_at?->toISOString(),
                    'icon' => 'heroicon-o-newspaper',
                    'tone' => 'success',
                    'type' => 'Article',
                    'title' => $article->title,
                    'subtitle' => $article->category ?: 'Published article',
                    'url' => ArticleResource::getUrl('view', ['record' => $article]),
                ])
                ->all(),
            ...HomeSection::query()
                ->where('status', PublishStatus::Published->value)
                ->whereNotNull('published_at')
                ->latest('published_at')
                ->limit(2)
                ->get()
                ->map(fn (HomeSection $section): array => [
                    'scope' => 'admin',
                    'timestamp' => $section->published_at?->toISOString(),
                    'icon' => 'heroicon-o-home',
                    'tone' => 'info',
                    'type' => 'Homepage',
                    'title' => $section->title,
                    'subtitle' => $section->key,
                    'url' => HomeSectionResource::getUrl('view', ['record' => $section]),
                ])
                ->all(),
        ]);

        return collect()
            ->merge($orders)
            ->merge($leads)
            ->merge($reports)
            ->merge($content)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staffActivity(): array
    {
        $reports = Report::query()
            ->with(['reporter'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Report $report): array => [
                'scope' => 'staff',
                'timestamp' => $report->created_at?->toISOString(),
                'icon' => 'heroicon-o-flag',
                'tone' => 'danger',
                'type' => 'Report',
                'title' => 'Report #'.$report->id,
                'subtitle' => collect([
                    $report->reporter?->name,
                    ucfirst(class_basename((string) $report->target_type)),
                    $report->reason,
                ])->filter()->implode(' · '),
                'url' => ReportResource::getUrl('view', ['record' => $report]),
            ]);

        $pendingPosts = Post::query()
            ->where('status', ContentStatus::Pending->value)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Post $post): array => [
                'scope' => 'staff',
                'timestamp' => $post->created_at?->toISOString(),
                'icon' => 'heroicon-o-light-bulb',
                'tone' => 'warning',
                'type' => 'Concept',
                'title' => $post->title,
                'subtitle' => 'Pending moderation',
                'url' => ModerationQueue::getUrl(),
            ]);

        $pendingComments = Comment::query()
            ->where('status', ContentStatus::Pending->value)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Comment $comment): array => [
                'scope' => 'staff',
                'timestamp' => $comment->created_at?->toISOString(),
                'icon' => 'heroicon-o-chat-bubble-left-ellipsis',
                'tone' => 'warning',
                'type' => 'Comment',
                'title' => str($comment->content)->limit(70)->toString(),
                'subtitle' => 'Pending moderation',
                'url' => ModerationQueue::getUrl(),
            ]);

        return collect()
            ->merge($reports)
            ->merge($pendingPosts)
            ->merge($pendingComments)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, mixed>  $rows
     * @param  Collection<int, CarbonImmutable>  $days
     * @return array<string, float|int>
     */
    private function sumByDay(
        Collection $rows,
        Collection $days,
        callable $resolver,
        string $dateColumn = 'created_at',
    ): array {
        $series = $days
            ->mapWithKeys(fn (CarbonImmutable $day): array => [$day->toDateString() => 0])
            ->all();

        foreach ($rows as $row) {
            $timestamp = data_get($row, $dateColumn);

            if ($timestamp === null) {
                continue;
            }

            $key = $timestamp->toDateString();

            if (! array_key_exists($key, $series)) {
                continue;
            }

            $series[$key] += $resolver($row);
        }

        return $series;
    }

    /**
     * @return array{days: Collection<int, CarbonImmutable>, start: CarbonImmutable, end: CarbonImmutable}
     */
    private function dateWindow(int $days, string $column = 'created_at'): array
    {
        $end = now();
        $start = CarbonImmutable::instance($end)->subDays($days - 1)->startOfDay();
        $dates = collect(range($days - 1, 0))
            ->map(fn (int $offset): CarbonImmutable => CarbonImmutable::instance($end)->subDays($offset)->startOfDay());

        return [
            'days' => $dates,
            'start' => $start,
            'end' => CarbonImmutable::instance($end)->endOfDay(),
            'column' => $column,
        ];
    }

    /**
     * @param  Collection<int, CarbonImmutable>  $days
     * @return array<int, string>
     */
    private function labelsForDays(Collection $days): array
    {
        return $days->map(fn (CarbonImmutable $day): string => $day->format('M j'))->all();
    }

    private function countForPeriod($query, int $days, string $column = 'created_at', bool $previous = false): int
    {
        [$start, $end] = $this->periodBounds($days, $previous);

        return $query->whereBetween($column, [$start, $end])->count();
    }

    private function sumForPeriod($query, int $days, string $column, array $constraints = [], bool $previous = false): float
    {
        [$start, $end] = $this->periodBounds($days, $previous);

        foreach ($constraints as [$field, $operator, $value]) {
            $query->where($field, $operator, $value);
        }

        return round((float) $query->whereBetween('created_at', [$start, $end])->sum($column), 2);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function periodBounds(int $days, bool $previous = false): array
    {
        $end = CarbonImmutable::now()->endOfDay();

        if ($previous) {
            $end = $end->subDays($days);
        }

        return [
            $end->subDays($days - 1)->startOfDay(),
            $end,
        ];
    }
}
