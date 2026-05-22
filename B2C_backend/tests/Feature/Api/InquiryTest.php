<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_an_inquiry(): void
    {
        $response = $this->postJson('/api/inquiries', [
            'name' => 'Jane Doe',
            'company_name' => 'OXP Studio',
            'email' => 'jane@example.com',
            'phone' => '+82-10-5555-0101',
            'country' => 'South Korea',
            'inquiry_type' => 'Sample Request',
            'message' => 'We need pellets for a pilot hospitality project.',
            'source_page' => 'b2b:en',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reference', 'INQ-000001')
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.company_name', 'OXP Studio');

        $this->assertDatabaseHas('inquiries', [
            'name' => 'Jane Doe',
            'company_name' => 'OXP Studio',
            'email' => 'jane@example.com',
            'status' => 'new',
            'source_page' => 'b2b:en',
        ]);
    }

    public function test_inquiry_submission_requires_required_fields(): void
    {
        $this->postJson('/api/inquiries', [
            'name' => '',
            'company_name' => '',
            'email' => 'not-an-email',
            'inquiry_type' => '',
            'message' => '',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors([
                'name',
                'company_name',
                'email',
                'inquiry_type',
                'message',
            ]);
    }

    public function test_order_shipping_validation_is_localized_in_chinese(): void
    {
        $response = $this->withHeaders(['Accept-Language' => 'zh'])
            ->postJson('/api/orders', [
                'guest_email' => 'buyer@example.com',
                'shipping_method_code' => 'standard',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', '请检查标注的字段并重试。')
            ->assertJsonPath('errors.shipping_phone.0', '使用新配送地址时，请填写联系电话。');

        $this->assertStringNotContainsString('field is required', $response->json('errors.shipping_phone.0'));
        $this->assertStringNotContainsString('address id is not present', $response->json('errors.shipping_phone.0'));
    }

    public function test_order_shipping_validation_is_localized_in_korean(): void
    {
        $response = $this->withHeaders(['Accept-Language' => 'ko-KR,ko;q=0.9,en;q=0.8'])
            ->postJson('/api/orders', [
                'guest_email' => 'buyer@example.com',
                'shipping_method_code' => 'standard',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', '표시된 항목을 확인하고 다시 시도해 주세요.')
            ->assertJsonPath('errors.shipping_phone.0', '새 배송지를 사용할 경우 연락처를 입력해 주세요.');

        $this->assertStringNotContainsString('field is required', $response->json('errors.shipping_phone.0'));
        $this->assertStringNotContainsString('address id is not present', $response->json('errors.shipping_phone.0'));
    }

    public function test_inquiry_application_validation_is_localized_in_chinese_and_korean(): void
    {
        $payload = [
            'name' => 'Taylor Example',
            'company_name' => 'Example Studio',
            'email' => 'taylor@example.com',
            'message' => 'We are evaluating materials for a product concept.',
        ];

        $this->withHeaders(['Accept-Language' => 'zh-CN'])
            ->postJson('/api/inquiries', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', '请检查标注的字段并重试。')
            ->assertJsonPath('errors.inquiry_type.0', '请选择应用领域。');

        $this->withHeaders(['Accept-Language' => 'ko'])
            ->postJson('/api/inquiries', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', '표시된 항목을 확인하고 다시 시도해 주세요.')
            ->assertJsonPath('errors.inquiry_type.0', '응용 분야를 선택해 주세요.');
    }

    public function test_authentication_required_is_localized(): void
    {
        $this->withHeaders(['Accept-Language' => 'zh'])
            ->getJson('/api/notifications')
            ->assertUnauthorized()
            ->assertJsonPath('message', '请先登录以继续。');

        $this->withHeaders(['Accept-Language' => 'ko'])
            ->getJson('/api/notifications')
            ->assertUnauthorized()
            ->assertJsonPath('message', '계속하려면 로그인해 주세요.');
    }

    public function test_missing_api_endpoint_is_localized(): void
    {
        $this->withHeaders(['Accept-Language' => 'zh'])
            ->getJson('/api/not-a-real-endpoint')
            ->assertNotFound()
            ->assertJsonPath('message', '找不到请求的接口。');

        $this->withHeaders(['Accept-Language' => 'ko'])
            ->getJson('/api/not-a-real-endpoint')
            ->assertNotFound()
            ->assertJsonPath('message', '요청한 API 엔드포인트를 찾을 수 없습니다.');
    }

    public function test_unsupported_accept_language_falls_back_to_english(): void
    {
        $this->withHeaders(['Accept-Language' => 'fr-FR,fr;q=0.9'])
            ->postJson('/api/orders', [
                'guest_email' => 'buyer@example.com',
                'shipping_method_code' => 'standard',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Please check the highlighted fields and try again.')
            ->assertJsonPath('errors.shipping_phone.0', 'Please enter a phone number when using a new shipping address.');
    }
}
