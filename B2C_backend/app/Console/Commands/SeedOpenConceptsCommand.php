<?php

namespace App\Console\Commands;

use App\Enums\PublishStatus;
use App\Models\HomeSection;
use Illuminate\Console\Command;

class SeedOpenConceptsCommand extends Command
{
    protected $signature = 'cms:seed-open-concepts';

    protected $description = 'Seed the community/open_concepts HomeSection with default concept cards if not yet admin-edited.';

    private const DEFAULT_ITEMS = [
        [
            'key' => 'chef-table-collection',
            'media_url' => '/images/application-tableware.jpg',
            'title_translations' => ['en' => "Chef's Table Capsule", 'ko' => '셰프 테이블 캡슐 컬렉션', 'zh' => '主厨餐桌限定系列'],
            'summary_translations' => [
                'en' => 'A limited dining collection co-developed with chefs exploring lighter premium service ware.',
                'ko' => '더 가벼운 프리미엄 서비스웨어를 검토하는 셰프와 함께 개발하는 한정 다이닝 컬렉션입니다.',
                'zh' => '与主厨共同开发的限量餐饮系列，探索更轻盈的高端服务器皿。',
            ],
            'stage_translations' => ['en' => 'Prototype review', 'ko' => '프로토타입 검토', 'zh' => '原型评审'],
            'support_type_translations' => ['en' => 'Design collaboration', 'ko' => '디자인 협업', 'zh' => '设计协作'],
            'focus_translations' => ['en' => 'Hospitality', 'ko' => '호스피탈리티', 'zh' => '餐饮酒店'],
            'tags_en' => 'tableware, chef-led, sampling',
            'tags_ko' => '테이블웨어, 셰프 협업, 소재 검토',
            'tags_zh' => '餐具, 主厨合作, 材料评估',
        ],
        [
            'key' => 'coastal-home-line',
            'media_url' => '/images/application-retail.jpg',
            'title_translations' => ['en' => 'Coastal Home Line', 'ko' => '코스탈 홈 라인', 'zh' => '海岸家居系列'],
            'summary_translations' => [
                'en' => 'A concept family of trays, stands, and home accents designed for calm domestic spaces.',
                'ko' => '차분한 생활 공간을 위해 설계한 트레이, 스탠드, 홈 오브제 콘셉트 라인입니다.',
                'zh' => '面向安静居家空间的托盘、支架与家居点缀概念系列。',
            ],
            'stage_translations' => ['en' => 'Seeking retail partner', 'ko' => '리테일 파트너 모집', 'zh' => '寻找零售合作方'],
            'support_type_translations' => ['en' => 'Concept support', 'ko' => '콘셉트 지원', 'zh' => '概念支持'],
            'focus_translations' => ['en' => 'Homeware', 'ko' => '홈웨어', 'zh' => '家居用品'],
            'tags_en' => 'home objects, retail, branding',
            'tags_ko' => '홈 오브제, 리테일, 브랜딩',
            'tags_zh' => '家居物件, 零售, 品牌化',
        ],
        [
            'key' => 'material-lab-fund',
            'media_url' => '/images/process-refined.jpg',
            'title_translations' => ['en' => 'OXP Material Lab Fund', 'ko' => 'OXP 소재 랩 펀드', 'zh' => 'OXP 材料实验基金'],
            'summary_translations' => [
                'en' => 'A small funding track for pilot moulds, review sets, and student or studio-led concept validation.',
                'ko' => '파일럿 몰드, 검토 세트, 학생 및 스튜디오 주도 콘셉트 검증을 위한 소규모 지원 트랙입니다.',
                'zh' => '面向试验模具、评估材料组合，以及学生或工作室主导概念验证的小型资助通道。',
            ],
            'stage_translations' => ['en' => 'Open for applicants', 'ko' => '신청 접수 중', 'zh' => '开放申请'],
            'support_type_translations' => ['en' => 'Fundraising support', 'ko' => '펀딩 지원', 'zh' => '资金支持'],
            'focus_translations' => ['en' => 'Material experimentation', 'ko' => '소재 실험', 'zh' => '材料实验'],
            'tags_en' => 'fund, student studios, pilot moulds',
            'tags_ko' => '펀드, 학생 스튜디오, 파일럿 몰드',
            'tags_zh' => '资助, 学生工作室, 试验模具',
        ],
    ];

    public function handle(): int
    {
        /** @var HomeSection|null $existing */
        $existing = HomeSection::where('page_key', 'community')
            ->where('key', 'open_concepts')
            ->first();

        if ($existing !== null) {
            $payload = $existing->payload ?? [];
            $items = $payload['items'] ?? [];

            if ($existing->is_seeded === false) {
                $this->info('Skipping: open_concepts record has been admin-edited (is_seeded=false).');

                return self::SUCCESS;
            }

            if (is_array($items) && count($items) > 0) {
                $this->info('Skipping: open_concepts record already has payload.items.');

                return self::SUCCESS;
            }

            $existing->payload = array_merge($payload, ['items' => self::DEFAULT_ITEMS]);
            $existing->status = PublishStatus::Published->value;
            $existing->is_seeded = true;
            $existing->save();

            $this->info('Updated existing open_concepts record with default concept cards.');

            return self::SUCCESS;
        }

        HomeSection::create([
            'page_key' => 'community',
            'key' => 'open_concepts',
            'status' => PublishStatus::Published->value,
            'is_seeded' => true,
            'sort_order' => 10,
            'payload' => ['items' => self::DEFAULT_ITEMS],
        ]);

        $this->info('Created community/open_concepts HomeSection with default concept cards.');

        return self::SUCCESS;
    }
}
