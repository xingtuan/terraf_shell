<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = collect([
            [
                'slug' => 'product-design',
                'name' => 'Product Design',
                'name_ko' => '제품 디자인',
                'name_zh' => '产品设计',
                'sort_order' => 1,
            ],
            [
                'slug' => 'material-application',
                'name' => 'Material Application',
                'name_ko' => '소재 응용',
                'name_zh' => '材料应用',
                'sort_order' => 2,
            ],
            [
                'slug' => 'sustainable-ideas',
                'name' => 'Sustainable Ideas',
                'name_ko' => '지속 가능한 아이디어',
                'name_zh' => '可持续创意',
                'sort_order' => 3,
            ],
            [
                'slug' => 'tableware-concepts',
                'name' => 'Tableware Concepts',
                'name_ko' => '식기 컨셉',
                'name_zh' => '餐具概念',
                'sort_order' => 4,
            ],
            [
                'slug' => 'packaging-design',
                'name' => 'Packaging Design',
                'name_ko' => '패키징 디자인',
                'name_zh' => '包装设计',
                'sort_order' => 5,
            ],
            [
                'slug' => 'b2b-collaboration',
                'name' => 'B2B Collaboration',
                'name_ko' => '기업 협업',
                'name_zh' => '企业合作',
                'sort_order' => 6,
            ],
            [
                'slug' => 'funding-projects',
                'name' => 'Funding Projects',
                'name_ko' => '펀딩 프로젝트',
                'name_zh' => '众筹项目',
                'sort_order' => 7,
            ],
        ]);

        Category::query()
            ->whereNotIn('slug', $categories->pluck('slug'))
            ->delete();

        $categories->each(function (array $category): void {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'name_ko' => $category['name_ko'],
                    'name_zh' => $category['name_zh'],
                    'description' => null,
                    'is_active' => true,
                    'sort_order' => $category['sort_order'],
                ]
            );
        });
    }
}
