<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\LineRichMenu;
use App\Services\Fortune\FortunePredictionMenu;
use App\Services\FortuneConversationService;
use App\Services\FortuneRichMenuService;
use App\Services\LineFortuneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 🧭 ระหว่างทำนาย กด/พิมพ์ "ดูดวง" ต้องได้ข้อความบอกขั้นที่รออยู่ ไม่ใช่เงียบ (2026-10-24)
 *
 * เคสจริง reading 14367: บอทถามเวลาเกิด → ลูกค้ากด "ดูดวง" บนเมนู → silent_skip
 *   → ลูกค้าไม่รู้ว่าบอทรออะไร ค้าง 22 นาที
 *
 * ล็อกไว้:
 *   1. ขั้นเวลาเกิด / วันเกิด / คำถาม / เปิดไพ่ → ข้อความเตือนตรงขั้น · state ไม่ขยับ · ไม่สร้างบิลใหม่
 *   2. AI กำลังคิด → "ดูดวง" ยังเงียบเหมือนเดิม แต่ปุ่ม "กำลังทำนาย" บนเมนูได้คำตอบ
 *   3. ปุ่ม "กำลังทำนาย" ตอนทำนายจบแล้ว → บอกว่าไม่มีการทำนายค้าง (ไม่ไหลเข้า AI)
 *   4. เมนูช่วงทำนาย = เมนูหลักที่ปุ่มดูดวงถูกแทน · สลับเมนูรายคนตามสถานะบิล
 */
class FortunePredictionStepReminderTest extends TestCase
{
    use RefreshDatabase;

    private const UID = 'U136730c4ce16c31ce574a61d6f460492';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();
    }

    private function service(): FortuneConversationService
    {
        return (new FortuneConversationService(FortuneTellingSetting::getSettings()))->setPlatform('line');
    }

    private function celticReading(string $status, array $state = []): FortuneReading
    {
        $r = new FortuneReading;
        $r->forceFill([
            'platform' => 'line',
            'facebook_user_id' => self::UID,
            'platform_user_id' => self::UID,
            'facebook_user_name' => 'ทดสอบ',
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => $status,
            'is_paid' => true,
            'questions' => [],
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
            'conversation_state' => $state,
        ])->save();

        return $r;
    }

    #[Test]
    public function fortune_keyword_while_waiting_for_birth_time_reminds_the_step_instead_of_going_silent(): void
    {
        $reading = $this->celticReading(FortuneReading::STATUS_CELTIC_AWAITING_QUESTION, [
            'celtic_birthtime_pending' => true,
        ]);
        $before = FortuneReading::count();

        $result = $this->service()->processMessage(self::UID, 'ดูดวง');

        $this->assertSame('celtic_step_reminder', $result['action']);
        $this->assertStringContainsString('เวลาเกิด', $result['message']);
        $this->assertSame([['title' => '🕛 ไม่ทราบเวลาเกิด', 'text' => 'ไม่ทราบเวลาเกิด']], $result['quick_replies']);
        $this->assertSame(FortuneReading::STATUS_CELTIC_AWAITING_QUESTION, $reading->fresh()->conversation_status);
        $this->assertTrue((bool) $reading->fresh()->getConversationState('celtic_birthtime_pending'), 'ห้ามแตะ state');
        $this->assertSame($before, FortuneReading::count(), 'ห้ามสร้างบิลใหม่');
    }

    #[Test]
    public function fortune_keyword_while_waiting_for_birth_date_asks_for_the_date(): void
    {
        $this->celticReading(FortuneReading::STATUS_CELTIC_AWAITING_QUESTION, ['celtic_birthdate_pending' => true]);

        $result = $this->service()->processMessage(self::UID, '99');

        $this->assertSame('celtic_step_reminder', $result['action']);
        $this->assertStringContainsString('วันเดือนปีเกิด', $result['message']);
    }

    #[Test]
    public function fortune_keyword_in_question_stage_asks_for_a_question(): void
    {
        $this->celticReading(FortuneReading::STATUS_CELTIC_AWAITING_QUESTION);

        $result = $this->service()->processMessage(self::UID, 'ดูดวง');

        $this->assertSame('celtic_step_reminder', $result['action']);
        $this->assertStringContainsString('คำถาม', $result['message']);
        $this->assertSame([], $result['quick_replies']);
    }

    #[Test]
    public function fortune_keyword_while_picking_cards_reminds_to_open_the_next_card(): void
    {
        $this->celticReading(FortuneReading::STATUS_CELTIC_PICKING);

        $result = $this->service()->processMessage(self::UID, 'ดูดวง');

        $this->assertSame('celtic_restart_hint', $result['action']);
        $this->assertStringContainsString('พร้อม', $result['message']);
    }

    #[Test]
    public function while_ai_is_thinking_the_fortune_keyword_stays_silent_but_the_menu_button_answers(): void
    {
        $this->celticReading(FortuneReading::STATUS_CELTIC_GENERATING);

        $silent = $this->service()->processMessage(self::UID, 'ดูดวง');
        $this->assertSame('silent_skip_in_prediction', $silent['action']);

        $asked = $this->service()->processMessage(self::UID, FortunePredictionMenu::STEP_REQUEST_TEXT);
        $this->assertSame('celtic_step_reminder', $asked['action']);
        $this->assertStringContainsString('กำลังทำนาย', $asked['message']);
    }

    #[Test]
    public function menu_button_after_the_reading_finished_says_nothing_is_pending(): void
    {
        $this->celticReading(FortuneReading::STATUS_COMPLETED);

        $result = $this->service()->processMessage(self::UID, FortunePredictionMenu::STEP_REQUEST_TEXT);

        $this->assertSame('celtic_step_reminder', $result['action']);
        $this->assertStringContainsString('ไม่มีการทำนายค้าง', $result['message']);
    }

    #[Test]
    public function predicting_menu_config_replaces_only_the_fortune_button(): void
    {
        $svc = new FortuneRichMenuService(FortuneTellingSetting::getSettings());
        $main = $svc->getDefaultEditorConfig();

        $pred = $svc->buildPredictingEditorConfig($main);

        $this->assertNotNull($pred);
        $this->assertSame(FortunePredictionMenu::STEP_REQUEST_TEXT, $pred['buttons'][0]['action_data']);
        $this->assertSame('กำลังทำนาย', $pred['buttons'][0]['label']);
        $this->assertSame(array_slice($main['buttons'], 1), array_slice($pred['buttons'], 1), 'ปุ่มอื่นต้องเหมือนเดิม');
        $this->assertNotContains('ดูดวง', array_column($pred['buttons'], 'action_data'));

        $noFortune = $main;
        $noFortune['buttons'] = array_slice($main['buttons'], 1);
        $this->assertNull($svc->buildPredictingEditorConfig($noFortune), 'ไม่มีปุ่มดูดวง = ไม่ต้องมีเมนูช่วงทำนาย');
    }

    #[Test]
    public function sync_links_the_predicting_menu_while_predicting_and_the_main_menu_after(): void
    {
        LineRichMenu::create([
            'rich_menu_id' => 'richmenu-main',
            'name' => FortuneRichMenuService::MAIN_MENU_NAME,
            'areas' => [],
            'editor_mode' => 'config',
            'is_default' => true,
            'is_active' => true,
        ]);
        LineRichMenu::create([
            'rich_menu_id' => 'richmenu-pred',
            'name' => FortuneRichMenuService::PREDICTING_MENU_NAME,
            'areas' => [],
            'editor_mode' => 'config',
            'is_default' => false,
            'is_active' => true,
        ]);

        $line = $this->createMock(LineFortuneService::class);
        $linked = [];
        $line->method('linkRichMenuToUser')->willReturnCallback(function ($uid, $id) use (&$linked) {
            $linked[] = $id;

            return true;
        });
        $line->method('getUserRichMenuId')->willReturn(null);

        $menus = $this->getMockBuilder(FortuneRichMenuService::class)
            ->setConstructorArgs([FortuneTellingSetting::getSettings()])
            ->onlyMethods(['ensurePredictingMenu', 'getLineService'])
            ->getMock();
        $menus->method('ensurePredictingMenu')->willReturn('richmenu-pred');
        $menus->method('getLineService')->willReturn($line);

        $switcher = new FortunePredictionMenu($menus);
        $reading = $this->celticReading(FortuneReading::STATUS_CELTIC_PICKING);

        $this->assertSame('richmenu-pred', $switcher->sync(self::UID));
        $this->assertSame('richmenu-pred', $switcher->sync(self::UID), 'ผูกแล้วไม่ยิง API ซ้ำ');

        $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);
        $this->assertSame(1, $switcher->sweep());

        $this->assertSame(['richmenu-pred', 'richmenu-main'], $linked);
    }

    #[Test]
    public function unknown_menu_after_cache_loss_relinks_main_when_the_lookup_cannot_confirm(): void
    {
        LineRichMenu::create([
            'rich_menu_id' => 'richmenu-main',
            'name' => FortuneRichMenuService::MAIN_MENU_NAME,
            'areas' => [],
            'editor_mode' => 'config',
            'is_default' => true,
            'is_active' => true,
        ]);
        // ทำนายจบแล้ว (มีบิลจ่ายเงินเร็ว ๆ นี้) + cache ถูกล้าง → ไม่รู้ว่าผูกเมนูไหนอยู่
        $this->celticReading(FortuneReading::STATUS_COMPLETED);

        $linked = [];
        $line = $this->createMock(LineFortuneService::class);
        // null = API ล้ม หรือ 404 — แยกไม่ได้ ต้องไม่ถือว่า "อยู่เมนูหลักแล้ว"
        $line->method('getUserRichMenuId')->willReturnOnConsecutiveCalls(null, 'richmenu-main');
        $line->method('linkRichMenuToUser')->willReturnCallback(function ($uid, $id) use (&$linked) {
            $linked[] = $id;

            return true;
        });

        $menus = $this->getMockBuilder(FortuneRichMenuService::class)
            ->setConstructorArgs([FortuneTellingSetting::getSettings()])
            ->onlyMethods(['getLineService'])
            ->getMock();
        $menus->method('getLineService')->willReturn($line);
        $switcher = new FortunePredictionMenu($menus);

        $this->assertSame('richmenu-main', $switcher->sync(self::UID));
        $this->assertSame(['richmenu-main'], $linked, 'lookup ไม่ยืนยัน → ผูกเมนูหลักซ้ำ');

        // lookup ยืนยันว่าอยู่เมนูหลัก → ไม่ต้องผูกซ้ำ
        Cache::flush();
        $this->assertSame('richmenu-main', $switcher->sync(self::UID));
        $this->assertSame(['richmenu-main'], $linked);
    }

    #[Test]
    public function sync_ignores_non_line_ids(): void
    {
        $menus = $this->createMock(FortuneRichMenuService::class);
        $menus->expects($this->never())->method('getLineService');

        $this->assertNull((new FortunePredictionMenu($menus))->sync('28641795802177235'));
    }
}
