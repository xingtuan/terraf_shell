<?php

namespace Tests\Feature\Admin;

use App\Enums\PublishStatus;
use App\Filament\Resources\HomeSections\Schemas\HomeSectionForm;
use App\Models\HomeSection;
use App\Models\MaterialSpec;
use App\Support\HomeSectionPayloadNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeSectionFactSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_fact_sheet_form_state_saves_items_and_info_cards_for_science_and_material_sections(): void
    {
        foreach ([['home', 'science_block'], ['material', 'material_facts']] as [$pageKey, $key]) {
            HomeSection::query()
                ->where('page_key', $pageKey)
                ->where('key', $key)
                ->delete();

            $record = HomeSection::factory()->published()->create([
                'page_key' => $pageKey,
                'key' => $key,
                'payload' => [],
            ]);

            $data = HomeSectionForm::applyPayloadState(
                [
                    'page_key' => $pageKey,
                    'key' => $key,
                ],
                [
                    'payload' => [
                        'items' => [
                            [
                                'key' => 'weight',
                                'icon' => 'feather',
                                'label_translations' => ['en' => 'Weight'],
                                'value_translations' => ['en' => 'Lightweight'],
                                'detail_translations' => ['en' => 'Portable object suitable.'],
                            ],
                        ],
                        'sheet_title_translations' => ['en' => 'Material Sheet'],
                        'sheet_description_translations' => ['en' => 'Editable sheet copy.'],
                        'sheet_cta_label_translations' => ['en' => 'Download sheet'],
                        'sheet_cta_url' => 'b2b?leadType=sample_request#inquiry',
                        'info_cards' => [
                            [
                                'key' => 'sampling',
                                'label_translations' => ['en' => 'Sampling'],
                                'value_translations' => ['en' => 'Available'],
                            ],
                        ],
                        'note_translations' => ['en' => 'Editable note.'],
                    ],
                ],
                $record,
            );

            $record->fill($data);
            $record->save();
            $record->refresh();

            $this->assertSame('weight', $record->payload['items'][0]['key']);
            $this->assertSame('Lightweight', $record->payload['items'][0]['value_translations']['en']);
            $this->assertSame('sampling', $record->payload['info_cards'][0]['key']);
            $this->assertSame('Editable sheet copy.', $record->payload['sheet_description_translations']['en']);
            $this->assertSame('Download sheet', $record->payload['sheet_cta_label_translations']['en']);
        }
    }

    public function test_payload_normalizer_preserves_items_and_info_cards_order(): void
    {
        $normalized = HomeSectionPayloadNormalizer::normalize([
            'items' => [
                '11111111-1111-4111-8111-111111111111' => ['key' => 'first'],
                '22222222-2222-4222-8222-222222222222' => ['key' => 'second'],
            ],
            'info_cards' => [
                '33333333-3333-4333-8333-333333333333' => ['key' => 'sampling'],
                '44444444-4444-4444-8444-444444444444' => ['key' => 'traceability'],
            ],
            'sheet_title_translations' => [
                'en' => 'Sheet',
                'zh' => '',
                'fr' => 'Ignored',
            ],
        ]);

        $this->assertSame(['first', 'second'], array_column($normalized['items'], 'key'));
        $this->assertSame(['sampling', 'traceability'], array_column($normalized['info_cards'], 'key'));
        $this->assertSame(['en' => 'Sheet'], $normalized['sheet_title_translations']);
    }

    public function test_fact_sheet_seed_command_writes_default_payloads(): void
    {
        MaterialSpec::factory()->published()->create([
            'key' => 'weight',
            'label' => 'Weight',
            'label_translations' => [
                'en' => 'Weight',
                'zh' => '重量',
                'ko' => '무게',
            ],
            'value' => 'Lightweight mineral composite',
            'value_translations' => [
                'en' => 'Lightweight mineral composite',
                'zh' => '轻量矿物复合材料',
                'ko' => '경량 미네랄 복합재',
            ],
            'detail' => 'Suitable for portable premium objects.',
            'detail_translations' => [
                'en' => 'Suitable for portable premium objects.',
                'zh' => '适用于便携式高端物件。',
                'ko' => '휴대 가능한 프리미엄 오브젝트에 적합합니다.',
            ],
            'icon' => 'feather',
            'sort_order' => 1,
        ]);

        $this->artisan('cms:seed-fact-sheet-sections')
            ->assertExitCode(0);

        foreach ([['home', 'science_block'], ['material', 'material_facts'], ['b2b', 'material_facts']] as [$pageKey, $key]) {
            $section = HomeSection::query()
                ->where('page_key', $pageKey)
                ->where('key', $key)
                ->firstOrFail();

            $this->assertTrue($section->is_seeded);
            $this->assertSame(PublishStatus::Published->value, $section->status);
            $this->assertSame('weight', $section->payload['items'][0]['key']);
            $this->assertSame('重量', $section->payload['items'][0]['label_translations']['zh']);
            $this->assertNotEmpty($section->payload['info_cards']);
            $this->assertSame('b2b?leadType=sample_request#inquiry', $section->payload['sheet_cta_url']);
            $this->assertArrayHasKey('sheet_description_translations', $section->payload);
        }
    }

    public function test_fact_sheet_seed_command_does_not_overwrite_admin_edited_records(): void
    {
        HomeSection::query()
            ->where('page_key', 'material')
            ->where('key', 'material_facts')
            ->delete();

        HomeSection::factory()->published()->create([
            'page_key' => 'material',
            'key' => 'material_facts',
            'title' => 'Admin title',
            'is_seeded' => false,
            'payload' => [
                'items' => [
                    [
                        'key' => 'admin-card',
                        'label_translations' => ['en' => 'Admin card'],
                    ],
                ],
                'sheet_title_translations' => ['en' => 'Admin sheet'],
                'info_cards' => [],
            ],
        ]);

        $this->artisan('cms:seed-fact-sheet-sections')
            ->assertExitCode(0);

        $section = HomeSection::query()
            ->where('page_key', 'material')
            ->where('key', 'material_facts')
            ->firstOrFail();

        $this->assertFalse($section->is_seeded);
        $this->assertSame('Admin title', $section->title);
        $this->assertSame('admin-card', $section->payload['items'][0]['key']);
        $this->assertSame('Admin sheet', $section->payload['sheet_title_translations']['en']);
        $this->assertNotEmpty($section->payload['info_cards']);
    }
}
