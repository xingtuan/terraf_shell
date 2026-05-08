<?php

namespace App\Filament\Widgets;

use App\Enums\PublishStatus;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\HomeSections\HomeSectionResource;
use App\Filament\Resources\Materials\MaterialResource;
use App\Filament\Support\PanelAccess;
use App\Models\Article;
use App\Models\HomeSection;
use App\Models\Material;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = null;

    protected ?string $description = null;

    protected function getHeading(): ?string
    {
        return __('admin.widgets.content_overview');
    }

    protected function getDescription(): ?string
    {
        return __('admin.ui.website_publishing_status_across_homepage_materials_and_articles');
    }

    protected function getStats(): array
    {
        $publishedMaterials = Material::query()->where('status', PublishStatus::Published->value)->count();
        $draftMaterials = Material::query()->where('status', PublishStatus::Draft->value)->count();
        $publishedArticles = Article::query()->where('status', PublishStatus::Published->value)->count();
        $draftArticles = Article::query()->where('status', PublishStatus::Draft->value)->count();
        $publishedSections = HomeSection::query()->where('status', PublishStatus::Published->value)->count();
        $draftSections = HomeSection::query()->where('status', PublishStatus::Draft->value)->count();

        return [
            Stat::make(__('admin.ui.homepage_sections_live'), number_format($publishedSections))
                ->description(__('admin.ui.draft_sections_count', ['count' => $draftSections]))
                ->color('info')
                ->icon('heroicon-o-home')
                ->url(HomeSectionResource::getUrl()),
            Stat::make(__('admin.ui.materials_published'), number_format($publishedMaterials))
                ->description(__('admin.ui.draft_material_records_count', ['count' => $draftMaterials]))
                ->color('success')
                ->icon('heroicon-o-sparkles')
                ->url(MaterialResource::getUrl()),
            Stat::make(
                __('admin.ui.featured_materials'),
                number_format(
                    Material::query()
                        ->where('status', PublishStatus::Published->value)
                        ->where('is_featured', true)
                        ->count(),
                ),
            )
                ->description(__('admin.ui.material_stories_promoted_on_key_landing_surfaces'))
                ->color('warning')
                ->icon('heroicon-o-star')
                ->url(MaterialResource::getUrl()),
            Stat::make(__('admin.ui.articles_published'), number_format($publishedArticles))
                ->description(__('admin.ui.draft_article_records_count', ['count' => $draftArticles]))
                ->color('success')
                ->icon('heroicon-o-newspaper')
                ->url(ArticleResource::getUrl()),
        ];
    }

    public static function canView(): bool
    {
        return PanelAccess::isAdmin();
    }
}
