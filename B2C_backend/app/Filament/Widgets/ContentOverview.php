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
    protected ?string $heading = 'Content Overview';

    protected ?string $description = 'Website publishing status across homepage, materials, and articles.';

    protected function getStats(): array
    {
        $publishedMaterials = Material::query()->where('status', PublishStatus::Published->value)->count();
        $draftMaterials = Material::query()->where('status', PublishStatus::Draft->value)->count();
        $publishedArticles = Article::query()->where('status', PublishStatus::Published->value)->count();
        $draftArticles = Article::query()->where('status', PublishStatus::Draft->value)->count();
        $publishedSections = HomeSection::query()->where('status', PublishStatus::Published->value)->count();
        $draftSections = HomeSection::query()->where('status', PublishStatus::Draft->value)->count();

        return [
            Stat::make('Homepage sections live', number_format($publishedSections))
                ->description("{$draftSections} draft sections")
                ->color('info')
                ->icon('heroicon-o-home')
                ->url(HomeSectionResource::getUrl()),
            Stat::make('Materials published', number_format($publishedMaterials))
                ->description("{$draftMaterials} draft material records")
                ->color('success')
                ->icon('heroicon-o-sparkles')
                ->url(MaterialResource::getUrl()),
            Stat::make(
                'Featured materials',
                number_format(
                    Material::query()
                        ->where('status', PublishStatus::Published->value)
                        ->where('is_featured', true)
                        ->count(),
                ),
            )
                ->description('Material stories promoted on key landing surfaces')
                ->color('warning')
                ->icon('heroicon-o-star')
                ->url(MaterialResource::getUrl()),
            Stat::make('Articles published', number_format($publishedArticles))
                ->description("{$draftArticles} draft article records")
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
