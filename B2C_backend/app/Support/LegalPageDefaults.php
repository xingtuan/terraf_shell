<?php

namespace App\Support;

final class LegalPageDefaults
{
    /**
     * @return array<string, array<string, array<string, string>>>
     */
    public static function content(): array
    {
        return [
            'privacy' => [
                'en' => [
                    'meta_title' => 'Privacy Policy | OXP',
                    'meta_description' => 'How OXP handles account, order, community, and inquiry information.',
                    'eyebrow' => 'Privacy Policy',
                    'title' => 'Privacy Policy',
                    'description' => 'This policy explains how OXP collects and uses information across the website, store, community, checkout, and B2B inquiry flows. Review it before launch.',
                    'last_updated_label' => 'Last updated',
                    'last_updated' => 'May 2026',
                    'body_html' => '<h2>Information we collect</h2><p>We may collect account details, contact details, order information, community submissions, uploaded files, inquiry details, and technical information needed to operate the service.</p><h2>How we use information</h2><p>We use information to provide the website, process order requests, support community features, respond to B2B inquiries, protect the service, and meet operational or legal requirements.</p><h2>Contact</h2><p>Privacy questions can be sent through the website contact page or to the contact email shown in the footer.</p>',
                ],
                'ko' => [
                    'meta_title' => '개인정보 처리방침 | OXP',
                    'meta_description' => 'OXP가 계정, 주문, 커뮤니티, 문의 정보를 처리하는 방식입니다.',
                    'eyebrow' => '개인정보 처리방침',
                    'title' => '개인정보 처리방침',
                    'description' => '이 예시 방침은 OXP 웹사이트, 스토어, 커뮤니티, 결제, B2B 문의 과정에서 정보를 수집하고 사용하는 방식을 설명합니다. 공개 전에 검토하세요.',
                    'last_updated_label' => '마지막 업데이트',
                    'last_updated' => '2026년 5월',
                    'body_html' => '<h2>수집하는 정보</h2><p>계정 정보, 연락처, 주문 정보, 커뮤니티 제출 내용, 업로드 파일, 문의 내용, 서비스 운영에 필요한 기술 정보를 수집할 수 있습니다.</p><h2>정보 사용 방법</h2><p>수집한 정보는 웹사이트 제공, 주문 요청 처리, 커뮤니티 기능 지원, B2B 문의 응대, 서비스 보호, 운영 또는 법적 요구사항 준수를 위해 사용됩니다.</p><h2>문의</h2><p>개인정보 관련 문의는 웹사이트 문의 페이지 또는 푸터에 표시된 연락처 이메일로 보낼 수 있습니다.</p>',
                ],
                'zh' => [
                    'meta_title' => '隐私政策 | OXP',
                    'meta_description' => '说明 OXP 如何处理账户、订单、社区和询盘相关信息。',
                    'eyebrow' => '隐私政策',
                    'title' => '隐私政策',
                    'description' => '本政策说明 OXP 在网站、商店、社区、结账和 B2B 询盘流程中如何收集和使用信息。上线前请完成审阅。',
                    'last_updated_label' => '最后更新',
                    'last_updated' => '2026年5月',
                    'body_html' => '<h2>我们收集的信息</h2><p>我们可能收集账户信息、联系方式、订单信息、社区提交内容、上传文件、询盘信息，以及运营服务所需的技术信息。</p><h2>我们如何使用信息</h2><p>我们使用这些信息来提供网站服务、处理订单请求、支持社区功能、回应 B2B 询盘、保护服务安全，并满足运营或法律要求。</p><h2>联系我们</h2><p>隐私相关问题可通过网站联系页面，或发送至页脚显示的联系邮箱。</p>',
                ],
            ],
            'terms' => [
                'en' => [
                    'meta_title' => 'Terms of Use | OXP',
                    'meta_description' => 'Terms for using the OXP website, store, guest checkout, community features, and B2B inquiry flow.',
                    'eyebrow' => 'Terms of Use',
                    'title' => 'Terms of Use',
                    'description' => 'These terms describe the general rules for using the OXP website, store, checkout, community features, and B2B inquiry flow. Review them before launch.',
                    'last_updated_label' => 'Last updated',
                    'last_updated' => 'May 2026',
                    'body_html' => '<h2>Using OXP</h2><p>You agree to use OXP lawfully, provide accurate information when placing orders or inquiries, and avoid interfering with the website or community features.</p><h2>Orders and inquiries</h2><p>Store orders, guest checkout requests, material requests, and B2B inquiries may require confirmation, availability checks, payment instructions, or follow-up before fulfilment.</p><h2>Content and community</h2><p>Community submissions must respect other users and may be reviewed, edited, hidden, or removed when needed to protect the platform.</p>',
                ],
                'ko' => [
                    'meta_title' => '이용약관 | OXP',
                    'meta_description' => 'OXP 웹사이트, 스토어, 비회원 결제, 커뮤니티 기능, B2B 문의 이용 조건입니다.',
                    'eyebrow' => '이용약관',
                    'title' => '이용약관',
                    'description' => '이 예시 약관은 OXP 웹사이트, 스토어, 결제, 커뮤니티 기능, B2B 문의 흐름을 이용하는 일반 규칙을 설명합니다. 공개 전에 검토하세요.',
                    'last_updated_label' => '마지막 업데이트',
                    'last_updated' => '2026년 5월',
                    'body_html' => '<h2>OXP 이용</h2><p>사용자는 OXP를 적법하게 이용하고, 주문 또는 문의 시 정확한 정보를 제공하며, 웹사이트나 커뮤니티 기능을 방해하지 않아야 합니다.</p><h2>주문 및 문의</h2><p>스토어 주문, 비회원 결제 요청, 소재 요청, B2B 문의는 처리 전에 확인, 재고 확인, 결제 안내 또는 후속 연락이 필요할 수 있습니다.</p><h2>콘텐츠와 커뮤니티</h2><p>커뮤니티 제출 내용은 다른 사용자를 존중해야 하며, 플랫폼 보호를 위해 검토, 수정, 숨김 또는 삭제될 수 있습니다.</p>',
                ],
                'zh' => [
                    'meta_title' => '使用条款 | OXP',
                    'meta_description' => '使用 OXP 网站、商店、游客结账、社区功能和 B2B 询盘流程的条款。',
                    'eyebrow' => '使用条款',
                    'title' => '使用条款',
                    'description' => '本条款说明使用 OXP 网站、商店、结账、社区功能和 B2B 询盘流程的一般规则。上线前请完成审阅。',
                    'last_updated_label' => '最后更新',
                    'last_updated' => '2026年5月',
                    'body_html' => '<h2>使用 OXP</h2><p>您同意合法使用 OXP，在下单或提交询盘时提供准确的信息，并避免干扰网站或社区功能。</p><h2>订单与询盘</h2><p>商店订单、游客结账请求、材料申请和 B2B 询盘在履行前可能需要确认、库存核查、付款说明或后续沟通。</p><h2>内容与社区</h2><p>社区提交内容应尊重其他用户。为保护平台，我们可能对内容进行审核、编辑、隐藏或移除。</p>',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{value: string, type: string, is_public: bool}>
     */
    public static function settings(): array
    {
        $settings = [];

        foreach (self::content() as $page => $locales) {
            foreach ($locales as $locale => $fields) {
                foreach ($fields as $field => $value) {
                    $settings["legal.{$page}.{$locale}.{$field}"] = [
                        'value' => $value,
                        'type' => 'string',
                        'is_public' => true,
                    ];
                }
            }
        }

        return $settings;
    }
}
