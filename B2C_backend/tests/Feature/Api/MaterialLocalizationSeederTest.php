<?php

namespace Tests\Feature\Api;

use Database\Seeders\MaterialContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialLocalizationSeederTest extends TestCase
{
    use RefreshDatabase;

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
}
