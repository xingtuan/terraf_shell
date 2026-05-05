<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateHomeSections();
        $this->updateArticles();
    }

    public function down(): void
    {
        // One-way content localization update.
    }

    private function updateHomeSections(): void
    {
        if (
            ! Schema::hasTable('home_sections') ||
            ! Schema::hasColumn('home_sections', 'title_translations')
        ) {
            return;
        }

        $sections = [
            'science_block' => [
                'title' => [
                    'en' => 'Science-backed qualities and specifications',
                    'zh' => '以科学验证的材料特性与规格',
                    'ko' => '과학적 근거를 갖춘 소재 특성과 사양',
                ],
                'subtitle' => [
                    'en' => 'Material proof points',
                    'zh' => '材料证明要点',
                    'ko' => '소재 검증 포인트',
                ],
                'content' => [
                    'en' => 'Frontend sections can render measured qualities, recycled-origin messaging, and application fit from the CMS payload.',
                    'zh' => '主页区块可以从 CMS 内容中展示可测量的材料特性、回收来源叙事和应用适配方向。',
                    'ko' => '홈페이지 섹션은 CMS 데이터를 기반으로 측정 가능한 품질, 재활용 원료 메시지, 적용 분야를 보여줄 수 있습니다.',
                ],
                'cta_label' => [
                    'en' => 'Review specs',
                    'zh' => '查看规格',
                    'ko' => '사양 보기',
                ],
            ],
            'latest_updates' => [
                'title' => [
                    'en' => 'Latest news and progress',
                    'zh' => '最新新闻与项目进展',
                    'ko' => '최신 소식과 진행 상황',
                ],
                'subtitle' => [
                    'en' => 'Articles and updates',
                    'zh' => '文章与更新',
                    'ko' => '아티클 및 업데이트',
                ],
                'content' => [
                    'en' => 'Published articles are exposed through the API and can be surfaced on the homepage dynamically.',
                    'zh' => '已发布的文章会通过 API 输出，并可以动态展示在主页。',
                    'ko' => '게시된 아티클은 API를 통해 제공되며 홈페이지에 동적으로 노출될 수 있습니다.',
                ],
                'cta_label' => [
                    'en' => 'Read updates',
                    'zh' => '阅读更新',
                    'ko' => '업데이트 읽기',
                ],
            ],
        ];

        foreach ($sections as $key => $fields) {
            DB::table('home_sections')
                ->where('key', $key)
                ->update($this->localizedStringUpdates('home_sections', $fields));
        }
    }

    private function updateArticles(): void
    {
        if (
            ! Schema::hasTable('articles') ||
            ! Schema::hasColumn('articles', 'title_translations')
        ) {
            return;
        }

        $articles = [
            'material-platform-launch' => [
                'title' => [
                    'en' => 'Material Platform Launch',
                    'zh' => '材料平台发布',
                    'ko' => '소재 플랫폼 출시',
                ],
                'excerpt' => [
                    'en' => 'The oyster-shell material showcase is now structured for premium storytelling, collaboration, and future support flows.',
                    'zh' => '牡蛎壳材料展示现已具备高级叙事、协作和未来支持流程的结构。',
                    'ko' => '굴 껍데기 소재 쇼케이스가 프리미엄 스토리텔링, 협업, 향후 지원 흐름을 담을 수 있는 구조로 정리되었습니다.',
                ],
                'content' => [
                    'en' => 'This launch establishes the backend foundation for premium material storytelling, editorial updates, and home page content management through the API.',
                    'zh' => '此次发布为高级材料叙事、编辑更新和通过 API 管理主页内容奠定了后端基础。',
                    'ko' => '이번 출시는 API를 통한 프리미엄 소재 스토리텔링, 에디토리얼 업데이트, 홈페이지 콘텐츠 관리를 위한 백엔드 기반을 마련합니다.',
                ],
                'category' => [
                    'en' => 'updates',
                    'zh' => '更新',
                    'ko' => '업데이트',
                ],
            ],
            'science-notes-shell-composite' => [
                'title' => [
                    'en' => 'Science Notes on the Oyster Shell Composite',
                    'zh' => '牡蛎壳复合材料科学笔记',
                    'ko' => '굴 껍데기 복합소재 과학 노트',
                ],
                'excerpt' => [
                    'en' => 'A high-level summary of performance, circularity, and positioning signals available for the frontend showcase.',
                    'zh' => '前端展示可使用的性能、循环性和定位信号的高层摘要。',
                    'ko' => '프론트엔드 쇼케이스에서 활용할 수 있는 성능, 순환성, 포지셔닝 신호의 요약입니다.',
                ],
                'content' => [
                    'en' => 'This editorial entry can be used by the frontend to render science-backed context around durability, circularity, and premium application fit.',
                    'zh' => '这篇编辑内容可用于前端展示围绕耐用性、循环性和高端应用适配的科学背景。',
                    'ko' => '이 에디토리얼 항목은 내구성, 순환성, 프리미엄 적용 적합성을 둘러싼 과학 기반 맥락을 프론트엔드에 제공하는 데 사용할 수 있습니다.',
                ],
                'category' => [
                    'en' => 'science',
                    'zh' => '科学',
                    'ko' => '과학',
                ],
            ],
        ];

        foreach ($articles as $slug => $fields) {
            DB::table('articles')
                ->where('slug', $slug)
                ->update($this->localizedStringUpdates('articles', $fields));
        }
    }

    /**
     * @param  array<string, array<string, string>>  $fields
     * @return array<string, mixed>
     */
    private function localizedStringUpdates(string $table, array $fields): array
    {
        $updates = [];

        foreach ($fields as $field => $translations) {
            $updates[$field] = $translations['en'];
            $updates[$field.'_translations'] = json_encode(
                $translations,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $updates['updated_at'] = now();
        }

        return $updates;
    }
};
