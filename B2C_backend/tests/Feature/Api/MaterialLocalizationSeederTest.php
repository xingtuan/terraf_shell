<?php

namespace Tests\Feature\Api;

use App\Enums\PublishStatus;
use App\Filament\Resources\HomeSections\Schemas\HomeSectionForm;
use App\Models\Article;
use App\Models\HomeSection;
use App\Models\Material;
use App\Models\MaterialApplication;
use App\Models\MaterialSpec;
use App\Models\MaterialStorySection;
use App\Models\User;
use App\Support\DefaultPageSections;
use Database\Seeders\MaterialContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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

    private const STORE_PAGE_SECTION_KEYS = [
        'intro',
        'product_grid',
        'applications',
        'credibility',
        'store_faq',
        'final_cta',
    ];

    private const COMMUNITY_PAGE_SECTION_KEYS = [
        'intro',
        'open_concepts',
        'final_cta',
    ];

    private const CONTACT_PAGE_SECTION_KEYS = [
        'intro',
        'details',
        'inquiry_form',
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
        foreach (['home', 'material', 'store', 'community', 'contact'] as $pageKey) {
            HomeSection::factory()->published()->create([
                'page_key' => $pageKey,
                'key' => 'shared_key',
            ]);
        }

        $this->assertSame(5, HomeSection::query()->where('key', 'shared_key')->count());
    }

    public function test_seeder_backfills_all_expected_page_sections(): void
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
        $storeKeys = HomeSection::query()
            ->where('page_key', 'store')
            ->pluck('key')
            ->all();
        $communityKeys = HomeSection::query()
            ->where('page_key', 'community')
            ->pluck('key')
            ->all();
        $contactKeys = HomeSection::query()
            ->where('page_key', 'contact')
            ->pluck('key')
            ->all();

        $this->assertSame([], array_values(array_diff(self::HOME_PAGE_SECTION_KEYS, $homeKeys)));
        $this->assertSame([], array_values(array_diff(self::MATERIAL_PAGE_SECTION_KEYS, $materialKeys)));
        $this->assertSame([], array_values(array_diff(self::STORE_PAGE_SECTION_KEYS, $storeKeys)));
        $this->assertSame([], array_values(array_diff(self::COMMUNITY_PAGE_SECTION_KEYS, $communityKeys)));
        $this->assertSame([], array_values(array_diff(self::CONTACT_PAGE_SECTION_KEYS, $contactKeys)));
        $this->assertSame(count(array_unique($homeKeys)), count($homeKeys));
        $this->assertSame(count(array_unique($materialKeys)), count($materialKeys));
        $this->assertSame(count(array_unique($storeKeys)), count($storeKeys));
        $this->assertSame(count(array_unique($communityKeys)), count($communityKeys));
        $this->assertSame(count(array_unique($contactKeys)), count($contactKeys));
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

    public function test_store_seeded_page_sections_preserve_nested_admin_payload_values(): void
    {
        HomeSection::query()
            ->where('page_key', 'store')
            ->where('key', 'store_faq')
            ->delete();

        HomeSection::query()->create([
            'page_key' => 'store',
            'key' => 'store_faq',
            'title' => 'Admin kept FAQ title',
            'title_translations' => ['en' => 'Admin kept FAQ title'],
            'payload' => [
                'items' => [
                    [
                        'question_translations' => [
                            'en' => 'Admin kept FAQ question',
                        ],
                    ],
                ],
            ],
            'is_seeded' => true,
            'status' => PublishStatus::Published->value,
            'sort_order' => 5,
            'published_at' => now(),
        ]);

        DefaultPageSections::backfill();

        $section = HomeSection::query()
            ->where('page_key', 'store')
            ->where('key', 'store_faq')
            ->firstOrFail();

        $this->assertSame('Admin kept FAQ title', $section->title);
        $this->assertSame('Admin kept FAQ question', $section->payload['items'][0]['question_translations']['en']);
        $this->assertArrayHasKey('zh', $section->payload['items'][0]['question_translations']);
        $this->assertArrayHasKey('answer_translations', $section->payload['items'][0]);
        $this->assertGreaterThan(1, count($section->payload['items']));
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

    public function test_admin_edited_store_and_community_sections_are_not_backfilled_over(): void
    {
        $this->seed(MaterialContentSeeder::class);

        $storeIntro = HomeSection::query()
            ->where('page_key', 'store')
            ->where('key', 'intro')
            ->firstOrFail();
        $communityIdeas = HomeSection::query()
            ->where('page_key', 'community')
            ->where('key', 'open_concepts')
            ->firstOrFail();

        $storeIntro->update([
            'title' => 'Admin store intro',
            'payload' => ['secondary_cta_url' => 'admin-material-route'],
            'is_seeded' => false,
            'status' => PublishStatus::Draft->value,
            'published_at' => null,
        ]);
        $communityIdeas->update([
            'title' => 'Admin open concepts',
            'payload' => ['focus_label' => 'Admin focus label'],
            'is_seeded' => false,
        ]);
        $contactForm = HomeSection::query()
            ->where('page_key', 'contact')
            ->where('key', 'inquiry_form')
            ->firstOrFail();
        $contactForm->update([
            'title' => 'Admin contact form',
            'payload' => ['form_anchor_id' => 'admin-inquiry'],
            'is_seeded' => false,
            'status' => PublishStatus::Draft->value,
            'published_at' => null,
        ]);

        $this->seed(MaterialContentSeeder::class);

        $this->assertSame('Admin store intro', $storeIntro->fresh()->title);
        $this->assertSame(['secondary_cta_url' => 'admin-material-route'], $storeIntro->fresh()->payload);
        $this->assertSame(PublishStatus::Draft->value, $storeIntro->fresh()->status);
        $this->assertNull($storeIntro->fresh()->published_at);
        $this->assertSame('Admin open concepts', $communityIdeas->fresh()->title);
        $this->assertSame(['focus_label' => 'Admin focus label'], $communityIdeas->fresh()->payload);
        $this->assertSame('Admin contact form', $contactForm->fresh()->title);
        $this->assertSame(['form_anchor_id' => 'admin-inquiry'], $contactForm->fresh()->payload);
        $this->assertSame(PublishStatus::Draft->value, $contactForm->fresh()->status);
    }

    public function test_public_and_admin_page_key_validation_does_not_fallback_to_home(): void
    {
        $this->assertContains('store', HomeSection::allowedPageKeys());
        $this->assertContains('community', HomeSection::allowedPageKeys());
        $this->assertContains('contact', HomeSection::allowedPageKeys());
        $this->assertContains('privacy', HomeSection::allowedPageKeys());
        $this->assertContains('terms', HomeSection::allowedPageKeys());

        $this->getJson('/api/home-sections?page=store')
            ->assertOk();

        $this->getJson('/api/home-sections?page=unsupported_page')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/home-sections', [
            'page_key' => 'unsupported_page',
            'key' => 'intro',
            'title' => 'Unsupported page',
            'status' => PublishStatus::Published->value,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page_key']);
    }

    public function test_draft_page_sections_are_hidden_publicly_but_visible_to_admin(): void
    {
        $this->seed(MaterialContentSeeder::class);

        $faq = HomeSection::query()
            ->where('page_key', 'store')
            ->where('key', 'store_faq')
            ->firstOrFail();

        $faq->update([
            'status' => PublishStatus::Draft->value,
            'published_at' => null,
        ]);
        $contactDetails = HomeSection::query()
            ->where('page_key', 'contact')
            ->where('key', 'details')
            ->firstOrFail();
        $contactDetails->update([
            'status' => PublishStatus::Draft->value,
            'published_at' => null,
        ]);

        $this->getJson('/api/home-sections?page=store')
            ->assertOk()
            ->assertJsonMissing([
                'key' => 'store_faq',
            ]);
        $this->getJson('/api/home-sections?page=contact')
            ->assertOk()
            ->assertJsonMissing([
                'key' => 'details',
            ]);

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/home-sections?page_key=store&status=draft')
            ->assertOk()
            ->assertJsonFragment([
                'page_key' => 'store',
                'key' => 'store_faq',
                'status' => PublishStatus::Draft->value,
            ]);
        $this->getJson('/api/admin/home-sections?page_key=contact&status=draft')
            ->assertOk()
            ->assertJsonFragment([
                'page_key' => 'contact',
                'key' => 'details',
                'status' => PublishStatus::Draft->value,
            ]);
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

        $storeResponse = $this->getJson('/api/home-sections?page=store')
            ->assertOk()
            ->assertJsonFragment(['page_key' => 'store', 'key' => 'store_faq']);

        foreach (self::STORE_PAGE_SECTION_KEYS as $key) {
            $storeResponse->assertJsonFragment(['key' => $key]);
        }

        $communityResponse = $this->getJson('/api/home-sections?page=community')
            ->assertOk()
            ->assertJsonFragment(['page_key' => 'community', 'key' => 'open_concepts']);

        foreach (self::COMMUNITY_PAGE_SECTION_KEYS as $key) {
            $communityResponse->assertJsonFragment(['key' => $key]);
        }

        $contactResponse = $this->getJson('/api/home-sections?page=contact')
            ->assertOk()
            ->assertJsonFragment(['page_key' => 'contact', 'key' => 'inquiry_form']);

        foreach (self::CONTACT_PAGE_SECTION_KEYS as $key) {
            $contactResponse->assertJsonFragment(['key' => $key]);
        }

        $introDefault = collect(DefaultPageSections::records())
            ->first(fn (array $record): bool => $record['page_key'] === 'material' && $record['key'] === 'intro');
        $storeFaqDefault = collect(DefaultPageSections::records())
            ->first(fn (array $record): bool => $record['page_key'] === 'store' && $record['key'] === 'store_faq');
        $communityIdeasDefault = collect(DefaultPageSections::records())
            ->first(fn (array $record): bool => $record['page_key'] === 'community' && $record['key'] === 'open_concepts');
        $contactDetailsDefault = collect(DefaultPageSections::records())
            ->first(fn (array $record): bool => $record['page_key'] === 'contact' && $record['key'] === 'details');
        $contactFormDefault = collect(DefaultPageSections::records())
            ->first(fn (array $record): bool => $record['page_key'] === 'contact' && $record['key'] === 'inquiry_form');

        $koIntro = collect($this->getJson('/api/page-sections?page=material&locale=ko')->json('data'))
            ->firstWhere('key', 'intro');
        $zhIntro = collect($this->getJson('/api/page-sections?page=material&locale=zh')->json('data'))
            ->firstWhere('key', 'intro');
        $zhStoreFaq = collect($this->getJson('/api/page-sections?page=store&locale=zh')->json('data'))
            ->firstWhere('key', 'store_faq');
        $koCommunityIdeas = collect($this->getJson('/api/page-sections?page=community&locale=ko')->json('data'))
            ->firstWhere('key', 'open_concepts');
        $zhContactDetails = collect($this->getJson('/api/page-sections?page=contact&locale=zh')->json('data'))
            ->firstWhere('key', 'details');
        $koContactForm = collect($this->getJson('/api/page-sections?page=contact&locale=ko')->json('data'))
            ->firstWhere('key', 'inquiry_form');

        $this->assertSame($introDefault['title_translations']['ko'], $koIntro['title']);
        $this->assertSame($introDefault['title_translations']['zh'], $zhIntro['title']);
        $this->assertSame($storeFaqDefault['title_translations']['zh'], $zhStoreFaq['title']);
        $this->assertIsArray($zhStoreFaq['payload']['items']);
        $this->assertGreaterThan(0, count($zhStoreFaq['payload']['items']));
        $this->assertArrayHasKey('question_translations', $zhStoreFaq['payload']['items'][0]);
        $this->assertArrayHasKey('zh', $zhStoreFaq['payload']['items'][0]['question_translations']);
        $this->assertSame($communityIdeasDefault['title_translations']['ko'], $koCommunityIdeas['title']);
        $this->assertSame(
            $communityIdeasDefault['payload']['focus_label_translations']['ko'],
            $koCommunityIdeas['payload']['focus_label_translations']['ko']
        );
        $this->assertSame('community/new', $koCommunityIdeas['payload']['cta_primary_url']);
        $this->assertSame($contactDetailsDefault['title_translations']['zh'], $zhContactDetails['title']);
        $this->assertSame('email', $zhContactDetails['payload']['cards'][0]['href_type']);
        $this->assertSame('contact#inquiry', $zhContactDetails['payload']['cards'][0]['href']);
        $this->assertSame(
            $contactDetailsDefault['payload']['response_translations']['zh'],
            $zhContactDetails['payload']['response_translations']['zh']
        );
        $this->assertSame($contactFormDefault['title_translations']['ko'], $koContactForm['title']);
        $this->assertSame('inquiry', $koContactForm['payload']['form_anchor_id']);
        $this->assertCount(7, $koContactForm['payload']['topic_options']);
        $this->assertSame('샘플 요청', $koContactForm['payload']['topic_options'][1]['label_translations']['ko']);
    }

    public function test_page_section_form_keeps_payload_hidden_keys_and_limits_translations_to_supported_locales(): void
    {
        HomeSection::query()
            ->where('page_key', 'material')
            ->where('key', 'intro')
            ->delete();

        $record = HomeSection::factory()->published()->create([
            'page_key' => 'material',
            'key' => 'intro',
            'payload' => [
                'variant' => 'intro',
                'secondary_cta_url' => 'contact',
                'secondary_cta_label_translations' => [
                    'en' => 'Old EN',
                    'zh' => 'Old ZH',
                    'ko' => 'Old KO',
                    'fr' => 'Old FR',
                ],
                'items' => [
                    [
                        'hidden_key' => 'keep-me',
                        'title_translations' => [
                            'en' => 'Old item EN',
                            'zh' => 'Old item ZH',
                            'ko' => 'Old item KO',
                            'fr' => 'Old item FR',
                        ],
                    ],
                ],
            ],
        ]);

        $data = HomeSectionForm::applyPayloadState(
            [
                'page_key' => 'material',
                'key' => 'intro',
                'title_translations' => [
                    'en' => 'Intro EN',
                    'zh' => 'Intro ZH',
                    'ko' => 'Intro KO',
                    'fr' => 'Intro FR',
                ],
            ],
            [
                'payload' => [
                    'secondary_cta_label_translations' => [
                        'en' => 'CTA EN',
                        'zh' => 'CTA ZH',
                        'ko' => 'CTA KO',
                        'fr' => 'CTA FR',
                    ],
                    'items' => [
                        [
                            'title_translations' => [
                                'en' => 'Item EN',
                                'zh' => 'Item ZH',
                                'ko' => 'Item KO',
                                'fr' => 'Item FR',
                            ],
                        ],
                    ],
                ],
            ],
            $record,
        );

        $this->assertSame([
            'en' => 'Intro EN',
            'ko' => 'Intro KO',
            'zh' => 'Intro ZH',
        ], $data['title_translations']);
        $this->assertSame('intro', $data['payload']['variant']);
        $this->assertSame('contact', $data['payload']['secondary_cta_url']);
        $this->assertSame([
            'en' => 'CTA EN',
            'ko' => 'CTA KO',
            'zh' => 'CTA ZH',
        ], $data['payload']['secondary_cta_label_translations']);
        $this->assertSame('keep-me', $data['payload']['items'][0]['hidden_key']);
        $this->assertSame([
            'en' => 'Item EN',
            'ko' => 'Item KO',
            'zh' => 'Item ZH',
        ], $data['payload']['items'][0]['title_translations']);
    }
}
