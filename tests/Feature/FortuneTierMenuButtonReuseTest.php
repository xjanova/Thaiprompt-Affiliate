<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ♻️ กดปุ่ม 39/99 จากเมนูแพคเกจ → ใช้แถวเมนูเดิมต่อเป็นบิล ไม่สร้างแถวใหม่ (2026-09-14)
 *
 * เคสจริง (PSID 28641795802177235): เห็นเมนู 22:35:19 → แถว FTU-260913-K3863
 *   กดปุ่ม TIER_DEEP_39 22:36:10 → ระบบปิดแถวเมนู แล้วสร้างแถวใหม่ FTU-260913-E7850 เป็นบิล
 *   ⇒ ลูกค้าคนเดียว 2 แถว แถวแรกค้างในหน้าบิลเป็น "คุยแล้วหายไป (ไม่เคยออกบิล)" — prod มี 2,060 แถวแบบนี้
 *
 * ล็อกไว้:
 *   1. กด 39/99 จากเมนูที่ยังเปิด → ออกบิลบนแถวเมนูเดิม จำนวนแถวไม่เพิ่ม
 *   2. แถวอื่นที่ค้างยังถูกปิดเหมือนเดิม (ยกเว้นแถวเมนูที่ใช้ต่อ)
 *   3. ไม่มีเมนู / เมนูหมดเวลา / คนละช่องทาง / มีร่องรอยบิล → สร้างแถวใหม่แบบเดิม
 *   4. แย่งแถวไม่ทัน (กด 39 กับ 99 พร้อมกัน) → สร้างแถวใหม่ ไม่เขียนทับบิลของอีกคำขอ
 *
 * ขั้นออกบิลจริง (UPA / QR / รูป) ถูกแทนด้วยตัวจด — เทสต์แค่ "ออกบิลบนแถวไหน"
 */
class FortuneTierMenuButtonReuseTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '28641795802177235';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        // ⚠️ getSettings() มี static memo ข้ามเทสต์ใน process เดียวกัน (ดู FortuneLateDeepSlipTest)
        FortuneTellingSetting::clearSettingsCache();
    }

    private function service(bool $stealAfterFind = false): object
    {
        $settings = FortuneTellingSetting::getSettings();
        $settings->enable_deep_reading = true;
        $settings->enable_celtic_cross = true;
        $settings->save();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();

        $svc = new class(FortuneTellingSetting::getSettings(), $stealAfterFind) extends FortuneConversationService
        {
            /** @var array<int, array{0: string, 1: int}> */
            public array $billedOn = [];

            public function __construct(FortuneTellingSetting $settings, public bool $stealAfterFind)
            {
                parent::__construct($settings);
            }

            public function pressTier(string $uid, string $tier): array
            {
                return $this->startDeepReadingFlow($uid, ['name' => 'ทดสอบ', 'id' => $uid], $tier);
            }

            protected function findTierMenuReadingToReuse(string $userId): ?FortuneReading
            {
                $menu = parent::findTierMenuReadingToReuse($userId);

                // จำลองอีกคำขอ (กดอีกแพคเกจพร้อมกัน) จองแถวเมนูไปก่อน ระหว่าง "หาเจอ" กับ "จอง"
                if ($menu !== null && $this->stealAfterFind) {
                    FortuneReading::whereKey($menu->id)->update(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT]);
                }

                return $menu;
            }

            protected function routePayFirstDeep(FortuneReading $reading, bool $skipPaymentGate = false): array
            {
                $this->billedOn[] = ['deep', $reading->id];

                return ['action' => 'pending_payment', 'message' => 'test', 'reading' => $reading];
            }

            protected function startCelticCrossFlow(FortuneReading $reading, bool $skipStripeGate = false): array
            {
                $this->billedOn[] = ['celtic', $reading->id];

                return ['action' => 'celtic_pending_payment', 'message' => 'test', 'reading' => $reading];
            }
        };
        $svc->setPlatform('facebook');

        return $svc;
    }

    /** แถวเมนูแบบที่ presentTierChoice() สร้าง */
    private function menuRow(array $attrs = [], ?array $state = null): FortuneReading
    {
        $r = new FortuneReading;
        $r->forceFill(array_merge([
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID,
            'platform_user_id' => self::PSID,
            'facebook_user_name' => 'ทดสอบ',
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => FortuneReading::STATUS_TIER_CHOICE,
            'questions' => [],
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
        ], $attrs, ['conversation_state' => $state ?? ['tier_choice_shown_at' => now()->toIso8601String()]]))->save();

        return $r;
    }

    #[Test]
    public function pressing_39_on_the_menu_turns_the_menu_row_into_the_bill(): void
    {
        $menu = $this->menuRow();
        $svc = $this->service();

        $svc->pressTier(self::PSID, 'deep');

        $this->assertSame([['deep', $menu->id]], $svc->billedOn);
        $this->assertSame(1, FortuneReading::count(), 'ห้ามงอกแถวใหม่');
        $fresh = $menu->fresh();
        $this->assertSame(FortuneReading::STATUS_COLLECTING_BIRTHDATE, $fresh->conversation_status);
        $this->assertSame($menu->bill_reference, $fresh->bill_reference, 'เลข FTU เดิม');
    }

    #[Test]
    public function pressing_99_reuses_the_menu_row_and_drops_a_stale_black_magic_flag(): void
    {
        $menu = $this->menuRow([], ['tier_choice_shown_at' => now()->toIso8601String(), 'black_magic_mode' => true]);
        $svc = $this->service();

        $svc->pressTier(self::PSID, 'celtic');

        $this->assertSame([['celtic', $menu->id]], $svc->billedOn);
        $this->assertSame(1, FortuneReading::count());
        $this->assertEmpty($menu->fresh()->getConversationState('black_magic_mode'), 'แถวใหม่ไม่เคยมีธงนี้ แถวที่ใช้ต่อก็ต้องไม่มี');
    }

    #[Test]
    public function other_open_conversations_are_still_closed(): void
    {
        $older = $this->menuRow([
            'reading_type' => FortuneReading::READING_TYPE_BASIC,
            'conversation_status' => FortuneReading::STATUS_NEW,
        ]);
        $menu = $this->menuRow();
        $svc = $this->service();

        $svc->pressTier(self::PSID, 'deep');

        $this->assertSame([['deep', $menu->id]], $svc->billedOn);
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $older->fresh()->conversation_status);
        $this->assertNotSame(FortuneReading::STATUS_COMPLETED, $menu->fresh()->conversation_status);
    }

    #[Test]
    public function without_a_menu_a_new_row_is_created_like_before(): void
    {
        $svc = $this->service();

        $svc->pressTier(self::PSID, 'deep');

        $this->assertSame(1, FortuneReading::count());
        $this->assertSame([['deep', FortuneReading::value('id')]], $svc->billedOn);
    }

    #[Test]
    public function a_menu_that_went_quiet_past_the_timeout_is_not_reused(): void
    {
        $menu = $this->menuRow();
        DB::table('fortune_readings')->where('id', $menu->id)->update([
            'updated_at' => now()->subMinutes(FortuneReading::PAYMENT_TIMEOUT_MINUTES + 10),
        ]);
        $svc = $this->service();

        $svc->pressTier(self::PSID, 'deep');

        $this->assertSame(2, FortuneReading::count());
        $this->assertNotSame($menu->id, $svc->billedOn[0][1]);
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $menu->fresh()->conversation_status);
    }

    #[Test]
    public function a_menu_from_another_channel_is_not_reused(): void
    {
        $menu = $this->menuRow(['platform' => 'line']);
        $svc = $this->service();

        $svc->pressTier(self::PSID, 'deep');

        $this->assertSame(2, FortuneReading::count());
        $this->assertNotSame($menu->id, $svc->billedOn[0][1]);
    }

    #[Test]
    public function a_menu_row_with_a_bill_trace_is_not_reused(): void
    {
        $menu = $this->menuRow(['unique_payment_amount_id' => 999999]);
        $svc = $this->service();

        $svc->pressTier(self::PSID, 'deep');

        $this->assertSame(2, FortuneReading::count());
        $this->assertNotSame($menu->id, $svc->billedOn[0][1]);
    }

    #[Test]
    public function losing_the_claim_falls_back_to_a_new_row_without_touching_the_other_bill(): void
    {
        $menu = $this->menuRow();
        $svc = $this->service(stealAfterFind: true);

        $svc->pressTier(self::PSID, 'celtic');

        $this->assertSame(2, FortuneReading::count());
        $this->assertNotSame($menu->id, $svc->billedOn[0][1]);
        // แถวที่อีกคำขอจองไปแล้ว ต้องไม่ถูกเขียนทับหรือปิดทิ้ง
        $this->assertSame(FortuneReading::STATUS_PENDING_PAYMENT, $menu->fresh()->conversation_status);
    }
}
