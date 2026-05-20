<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use App\Models\HomeSection;
use App\Models\Material;
use App\Models\MaterialApplication;
use App\Models\MaterialSpec;
use App\Models\MaterialStorySection;
use App\Support\DefaultPageSections;
use Database\Seeders\MaterialContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialLocalizationSeederTest extends TestCase
{
    use RefreshDatabase;

    private const HOME_PAGE_SECTION_KEYS = [
        'hero',
        'audience_paths',
        'business_pillars',
        'why_it_matters',
        'material_story',
        'open_source_legacy',
        'applications',
        'science_block',
        'collaboration',
        'credibility',
        'trust_and_credibility',
        'latest_updates',
        'pilot_projects',
        'final_cta',
        'footer',
    ];

    private const MATERIAL_PAGE_SECTION_KEYS = [
        'intro',
        'material_family',
        'why_it_matters',
        'material_story',
        'open_source_legacy',
        'applications',
        'material_facts',
        'proof_points',
        'certifications',
        'technical_downloads',
        'comparison',
        'credibility',
        'trust_and_credibility',
        'pilot_projects',
        'collaboration',
        'final_cta',
    ];

    public function test_homepage_endpoint_returns_chinese_material_data_from_seeded_cms_content(): void
    {
        $this->seed(MaterialContentSeeder::class);

        $this->getJson('/api/homepage?locale=zh')
            ->assertOk()
            ->assertJsonPath('data.materials.0.title', '高端牡蛎壳复合材料')
            ->assertJsonPath('data.materials.0.headline', '一种基于回收牡蛎壳、具备科学验证方向的高端材料平台。')
            ->assertJsonPath('data.materials.0.summary', '面向高端室内物件、酒店餐饮项目、餐具概念以及未来联合产品开发。');
    }

    public function test_material_endpoint_returns_chinese_material_specs_story_applications_and_demo_evidence(): void
    {
        $this->seed(MaterialContentSeeder::class);

        $response = $this->getJson('/api/materials?locale=zh')
            ->assertOk()
            ->assertJsonPath('data.name', '高端牡蛎壳复合材料')
            ->assertJsonPath('data.tagline', '一种基于回收牡蛎壳、具备科学验证方向的高端材料平台。')
            ->assertJsonPath('data.origin', '面向高端室内物件、酒店餐饮项目、餐具概念以及未来联合产品开发。')
            ->assertJsonPath('data.properties.0.label', '重量')
            ->assertJsonPath('data.properties.0.value', '轻量化矿物复合材料')
            ->assertJsonPath('data.process_steps.0.title', '牡蛎壳收集')
            ->assertJsonPath('data.process_steps.1.title', '清洗与热净化')
            ->assertJsonPath('data.process_steps.2.title', '复合制粒')
            ->assertJsonPath('data.process_steps.3.title', '压缩成型与表面处理')
            ->assertJsonPath('data.applications.0.title', '酒店餐饮与桌面物件')
            ->assertJsonPath('data.certifications.0.label', '吸水率测试')
            ->assertJsonPath('data.certifications.0.value', '0.00% 演示目标')
            ->assertJsonPath('data.certifications.0.status', 'demo')
            ->assertJsonPath('data.certifications.0.verified', false);

        $payload = json_encode($response->json(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        foreach ([
            'A premium, science-backed material platform built from recovered shell.',
            'Weight',
            'Lightweight',
            'From Shell Waste to Premium Feedstock',
            'Material Science for Commercial Credibility',
        ] as $forbiddenText) {
            $this->assertStringNotContainsString($forbiddenText, $payload);
        }
    }

    public function test_material_endpoint_returns_korean_material_specs_story_and_applications(): void
    {
        $this->seed(MaterialContentSeeder::class);

        $this->getJson('/api/materials?locale=ko')
            ->assertOk()
            ->assertJsonPath('data.name', '프리미엄 굴 껍데기 복합소재')
            ->assertJsonPath('data.tagline', '회수한 굴 껍데기를 기반으로 한 과학 검증 지향의 프리미엄 소재 플랫폼입니다.')
            ->assertJsonPath('data.origin', '프리미엄 인테리어 오브젝트, 호스피탈리티 프로그램, 테이블웨어 콘셉트, 향후 공동 제품 개발에 적합하도록 설계되었습니다.')
            ->assertJsonPath('data.properties.0.label', '무게')
            ->assertJsonPath('data.properties.0.value', '경량 미네랄 복합소재')
            ->assertJsonPath('data.process_steps.0.title', '굴 껍데기 수거')
            ->assertJsonPath('data.process_steps.1.title', '세척 및 열 정화')
            ->assertJsonPath('data.process_steps.2.title', '복합 펠릿화')
            ->assertJsonPath('data.process_steps.3.title', '압축 성형 및 마감')
            ->assertJsonPath('data.applications.0.title', '호스피탈리티 및 테이블웨어')
            ->assertJsonPath('data.certifications.0.label', '흡수율 테스트')
            ->assertJsonPath('data.certifications.0.value', '0.00% 데모 목표')
            ->assertJsonPath('data.certifications.0.status', 'demo')
            ->assertJsonPath('data.certifications.0.verified', false);
    }

    public function test_material_content_seeder_does_not_overwrite_admin_edited_cms_records(): void
    {
        $this->seed(MaterialContentSeeder::class);

        $material = Material::query()->where('slug', 'premium-oyster-shell')->firstOrFail();
        $spec = MaterialSpec::query()->where('material_id', $material->id)->where('sort_order', 1)->firstOrFail();
        $storySection = MaterialStorySection::query()->where('material_id', $material->id)->where('sort_order', 1)->firstOrFail();
        $application = MaterialApplication::query()->where('material_id', $material->id)->where('sort_order', 1)->firstOrFail();
        $article = Article::query()->where('slug', 'material-platform-launch')->firstOrFail();
        $homeSection = HomeSection::query()->where('key', 'hero')->firstOrFail();

        $counts = [
            'materials' => Material::query()->count(),
            'specs' => MaterialSpec::query()->where('material_id', $material->id)->count(),
            'story_sections' => MaterialStorySection::query()->where('material_id', $material->id)->count(),
            'applications' => MaterialApplication::query()->where('material_id', $material->id)->count(),
            'articles' => Article::query()->count(),
            'home_sections' => HomeSection::query()->count(),
        ];

        $material->update([
            'title' => 'Admin edited material title',
            'title_translations' => [
                'en' => 'Admin edited material title',
                'ko' => 'Admin KO material title',
                'zh' => 'Admin ZH material title',
            ],
        ]);
        $spec->update([
            'key' => 'admin_strength_key',
            'label' => 'Admin edited spec',
            'label_translations' => ['en' => 'Admin edited spec'],
        ]);
        $storySection->update([
            'title' => 'Admin edited story section',
            'title_translations' => ['en' => 'Admin edited story section'],
        ]);
        $application->update([
            'title' => 'Admin edited application',
            'title_translations' => ['en' => 'Admin edited application'],
        ]);
        $article->update([
            'title' => 'Admin edited article title',
            'content' => 'Admin edited article body.',
            'title_translations' => ['en' => 'Admin edited article title'],
            'content_translations' => ['en' => 'Admin edited article body.'],
        ]);
        $homeSection->update([
            'title' => 'Admin edited hero',
            'cta_label' => 'Admin edited CTA',
            'title_translations' => ['en' => 'Admin edited hero'],
            'cta_label_translations' => ['en' => 'Admin edited CTA'],
        ]);

        $this->seed(MaterialContentSeeder::class);

        $this->assertTrue($material->fresh()->is_seeded);
        $this->assertSame('Admin edited material title', $material->fresh()->title);
        $this->assertSame('admin_strength_key', $spec->fresh()->key);
        $this->assertSame('Admin edited story section', $storySection->fresh()->title);
        $this->assertSame('Admin edited application', $application->fresh()->title);
        $this->assertSame('Admin edited article title', $article->fresh()->title);
        $this->assertSame('Admin edited CTA', $homeSection->fresh()->cta_label);

        $this->assertSame($counts['materials'], Material::query()->count());
        $this->assertSame($counts['specs'], MaterialSpec::query()->where('material_id', $material->id)->count());
        $this->assertSame($counts['story_sections'], MaterialStorySection::query()->where('material_id', $material->id)->count());
        $this->assertSame($counts['applications'], MaterialApplication::query()->where('material_id', $material->id)->count());
        $this->assertSame($counts['articles'], Article::query()->count());
        $this->assertSame($counts['home_sections'], HomeSection::query()->count());

        $this->getJson('/api/homepage')
            ->assertOk()
            ->assertJsonPath('data.home_sections.0.title', 'Admin edited hero')
            ->assertJsonPath('data.materials.0.title', 'Admin edited material title')
            ->assertJsonPath('data.articles.0.title', 'Admin edited article title');
    }

    public function test_home_sections_support_duplicate_keys_across_page_keys(): void
    {
        HomeSection::factory()->published()->create([
            'page_key' => 'home',
            'key' => 'shared_key',
        ]);

        HomeSection::factory()->published()->create([
            'page_key' => 'material',
            'key' => 'shared_key',
        ]);

        $this->assertSame(2, HomeSection::query()->where('key', 'shared_key')->count());
    }

    public function test_seeder_backfills_all_expected_home_and_material_page_sections(): void
    {
        $this->seed(MaterialContentSeeder::class);
        $this->seed(MaterialContentSeeder::class);

        $homeKeys = HomeSection::query()
            ->where('page_key', 'home')
            ->pluck('key')
            ->all();
        $materialKeys = HomeSection::query()
            ->where('page_key', 'material')
            ->pluck('key')
            ->all();

        $this->assertSame([], array_values(array_diff(self::HOME_PAGE_SECTION_KEYS, $homeKeys)));
        $this->assertSame([], array_values(array_diff(self::MATERIAL_PAGE_SECTION_KEYS, $materialKeys)));
        $this->assertSame(count(array_unique($homeKeys)), count($homeKeys));
        $this->assertSame(count(array_unique($materialKeys)), count($materialKeys));
    }

    public function test_seeded_page_sections_only_fill_missing_fields_and_nested_payload_values(): void
    {
        HomeSection::query()
            ->where('page_key', 'home')
            ->where('key', 'hero')
            ->delete();

        HomeSection::query()->create([
            'page_key' => 'home',
            'key' => 'hero',
            'title' => 'Admin kept title',
            'title_translations' => ['en' => 'Admin kept title'],
            'content' => null,
            'content_translations' => [],
            'payload' => [
                'custom' => ['keep' => true],
                'metrics' => [
                    [
                        'label_translations' => [
                            'en' => 'Admin kept metric',
                        ],
                    ],
                ],
            ],
            'is_seeded' => true,
            'status' => 'published',
            'sort_order' => 1,
            'published_at' => now(),
        ]);

        DefaultPageSections::backfill();

        $section = HomeSection::query()
            ->where('page_key', 'home')
            ->where('key', 'hero')
            ->firstOrFail();

        $this->assertSame('Admin kept title', $section->title);
        $this->assertNotNull($section->content);
        $this->assertTrue($section->payload['custom']['keep']);
        $this->assertSame('Admin kept metric', $section->payload['metrics'][0]['label_translations']['en']);
        $this->assertArrayHasKey('zh', $section->payload['metrics'][0]['label_translations']);
        $this->assertSame('b2b', $section->payload['secondary_cta_url']);
    }

    public function test_admin_edited_page_sections_are_not_backfilled_over(): void
    {
        HomeSection::query()
            ->where('page_key', 'home')
            ->where('key', 'hero')
            ->delete();

        HomeSection::query()->create([
            'page_key' => 'home',
            'key' => 'hero',
            'title' => 'Admin-owned hero',
            'content' => null,
            'payload' => ['metrics' => []],
            'is_seeded' => false,
            'status' => 'published',
            'sort_order' => 1,
            'published_at' => now(),
        ]);

        DefaultPageSections::backfill();

        $section = HomeSection::query()
            ->where('page_key', 'home')
            ->where('key', 'hero')
            ->firstOrFail();

        $this->assertSame('Admin-owned hero', $section->title);
        $this->assertNull($section->content);
        $this->assertArrayNotHasKey('secondary_cta_url', $section->payload);
    }

    public function test_public_page_sections_endpoints_return_expected_keys_and_locales(): void
    {
        $this->seed(MaterialContentSeeder::class);

        $homeResponse = $this->getJson('/api/home-sections?page=home')
            ->assertOk()
            ->assertJsonFragment(['page_key' => 'home', 'key' => 'footer']);

        foreach (self::HOME_PAGE_SECTION_KEYS as $key) {
            $homeResponse->assertJsonFragment(['key' => $key]);
        }

        $materialResponse = $this->getJson('/api/home-sections?page=material')
            ->assertOk()
            ->assertJsonFragment(['page_key' => 'material', 'key' => 'intro']);

        foreach (self::MATERIAL_PAGE_SECTION_KEYS as $key) {
            $materialResponse->assertJsonFragment(['key' => $key]);
        }

        $introDefault = collect(DefaultPageSections::records())
            ->first(fn (array $record): bool => $record['page_key'] === 'material' && $record['key'] === 'intro');

        $koIntro = collect($this->getJson('/api/page-sections?page=material&locale=ko')->json('data'))
            ->firstWhere('key', 'intro');
        $zhIntro = collect($this->getJson('/api/page-sections?page=material&locale=zh')->json('data'))
            ->firstWhere('key', 'intro');

        $this->assertSame($introDefault['title_translations']['ko'], $koIntro['title']);
        $this->assertSame($introDefault['title_translations']['zh'], $zhIntro['title']);
    }
}
