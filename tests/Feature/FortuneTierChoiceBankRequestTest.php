<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\PaymentBankAccount;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 💳 ลูกค้าขอเลขบัญชีตอนยังไม่เลือกแพคเกจ → ต้องได้เลขบัญชีจริง (2026-09-19)
 *
 * เคสจริง Wanpen Pen (FB PSID 27219605077706175 · FTU-260918-R9957 · 2026-09-18):
 *   22:22 "ค่ะดูดวงค่ะ"                    → การ์ดเลือกแพคเกจ (tier_choice)
 *   22:24 "ขอหมายเลขธนาคารด้วยค่ะ"          → tier_choice_invalid 205 ตัว (ไม่ได้เลขบัญชี)
 *   22:27 "ส่วนตัวค่ะ ชื่อธนาคารด้วยค่ะ..."  → กล่องเดิมซ้ำเป๊ะ
 *   22:53 หมดเวลา · is_paid=0 — อยากจ่ายแต่ไม่เคยได้ช่องทางจ่าย
 *
 * ล็อกไว้:
 *   1. ขอเลขบัญชีตอนเปิดทั้ง 39/99 → ได้กล่อง payment_info (เลขบัญชี + QR) ไม่ใช่กล่องเลือกแพคเกจ
 *   2. กล่องนั้นต้องตั้งธงรอสลิป ไม่งั้นลูกค้าโอนแล้วส่งสลิปมาจะตกร่องเงียบ
 *   3. "ส่วนตัว" = ป้ายปุ่ม "ดู vip ส่วนตัว 99บาท" → เข้า Celtic ไม่ใช่กล่องจ่ายเงิน
 *   4. "เรื่องส่วนตัว" / "ถามส่วนตัว" = เรื่องลับที่ลูกค้าเล่า → ห้ามออกบิล 99 ให้เอง
 *   5. "ไม่มีพร้อมเพย์" → ห้ามยื่นพร้อมเพย์ไทยกลับไป
 *   6. แอดมินยังไม่ตั้งบัญชี/QR → คงกล่องเลือกแพคเกจไว้ (flow ต้องไม่ตัน)
 */
class FortuneTierChoiceBankRequestTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '27219605077706175';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        // ⚠️ getSettings() มี static memo ข้ามเทสต์ใน process เดียวกัน
        FortuneTellingSetting::clearSettingsCache();
    }

    /** บัญชีรับโอนแบบเดียวกับ prod (กสิกร + พร้อมเพย์) */
    private function bankAccount(): PaymentBankAccount
    {
        return PaymentBankAccount::create([
            'bank_name' => 'ธนาคารกสิกรไทย',
            'account_number' => '2323775349',
            'account_name' => 'จันทราพยากรณ์',
            'promptpay_id' => '0909335963',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * @param  bool  $withBank  false = แอดมินยังไม่ตั้งบัญชี/QR เลย
     */
    private function service(bool $withBank = true): object
    {
        if ($withBank) {
            $this->bankAccount();
        }

        $settings = FortuneTellingSetting::getSettings();
        $settings->enable_deep_reading = true;
        $settings->enable_celtic_cross = true;
        $settings->deep_reading_price = 39;
        $settings->celtic_cross_price = 99;
        $settings->save();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();

        $svc = new class(FortuneTellingSetting::getSettings()) extends FortuneConversationService
        {
            /** @var array<int, string> แพคเกจที่ถูกสั่งออกบิล (แทนขั้นสร้าง UPA/QR จริง) */
            public array $billedTier = [];

            public function tierChoice(FortuneReading $reading, string $text): array
            {
                return $this->handleTierChoice($reading, $text);
            }

            protected function routePayFirstDeep(FortuneReading $reading, bool $skipPaymentGate = false): array
            {
                $this->billedTier[] = 'deep';

                return ['action' => 'pending_payment', 'message' => 'test', 'reading' => $reading];
            }

            protected function startCelticCrossFlow(FortuneReading $reading, bool $skipStripeGate = false): array
            {
                $this->billedTier[] = 'celtic';

                return ['action' => 'celtic_pending_payment', 'message' => 'test', 'reading' => $reading];
            }

            /** ตัด AI ออก — เทสต์นี้สนใจว่า "เลือกสาขาไหน" ไม่ใช่ถ้อยคำที่ AI แต่ง */
            protected function buildAIAssistedStepReminder(
                string $messageText,
                string $stepHint,
                ?array $userProfile = null,
                ?string $flowContext = null
            ): string {
                return $stepHint;
            }
        };
        $svc->setPlatform('facebook');

        return $svc;
    }

    /** แถวเมนูแบบที่ presentTierChoice() สร้าง */
    private function menuRow(): FortuneReading
    {
        $r = new FortuneReading;
        $r->forceFill([
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID,
            'platform_user_id' => self::PSID,
            'facebook_user_name' => 'Wanpen Pen',
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => FortuneReading::STATUS_TIER_CHOICE,
            'questions' => [],
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
            'conversation_state' => ['tier_choice_shown_at' => now()->toIso8601String()],
        ])->save();

        return $r;
    }

    #[Test]
    public function asking_for_the_bank_number_returns_the_real_account_not_the_menu_again(): void
    {
        $svc = $this->service();
        $reading = $this->menuRow();

        $result = $svc->tierChoice($reading, 'ขอหมายเลขธนาคารด้วยค่ะ');

        $this->assertSame('payment_info', $result['action'], 'ต้องส่งกล่องช่องทางจ่าย ไม่ใช่กล่องเลือกแพคเกจ');
        $this->assertStringContainsString('2323775349', $result['message'], 'ต้องมีเลขบัญชีจริงในข้อความ');
        $this->assertStringContainsString('จันทราพยากรณ์', $result['message'], 'ต้องมีชื่อบัญชี');
        $this->assertSame([], $svc->billedTier, 'ยังไม่เลือกแพคเกจ = ยังไม่ออกบิล');
    }

    #[Test]
    public function the_bank_box_states_which_amount_buys_which_package(): void
    {
        $svc = $this->service();
        $reading = $this->menuRow();

        $message = $svc->tierChoice($reading, 'ขอเลขบัญชีค่ะ')['message'];

        // ยอดเป็นตัวตัดสินแพคเกจจริงที่ routePrepaySlipByAmount — ข้อความต้องบอกทั้งสองยอด
        $this->assertStringContainsString('39', $message);
        $this->assertStringContainsString('99', $message);
        $this->assertStringNotContainsString(
            'โอนตามยอดให้ตรงเป๊ะ',
            $message,
            'ยังไม่มีบิล = ไม่มียอดให้ตรงกับอะไร ห้ามบอกประโยคนี้'
        );
    }

    #[Test]
    public function the_bank_box_arms_the_slip_flag_so_a_transfer_is_not_lost(): void
    {
        $svc = $this->service();
        $reading = $this->menuRow();

        $this->assertFalse(Cache::has('fortune:returning_slip_ask:'.self::PSID));

        $svc->tierChoice($reading, 'ขอเลขบัญชีค่ะ');

        $this->assertTrue(
            Cache::has('fortune:returning_slip_ask:'.self::PSID),
            'ไม่ตั้งธง = ลูกค้าโอนแล้วส่งสลิปมาเฉย ๆ จะไม่มีใครตรวจ'
        );
    }

    #[Test]
    public function typing_just_suan_tua_picks_the_vip_package_from_the_button_label(): void
    {
        $svc = $this->service();
        $reading = $this->menuRow();

        // ข้อความจริงของลูกค้า: เลือกแพคเกจ + ขอเลขบัญชีในประโยคเดียว
        $svc->tierChoice($reading, 'ส่วนตัวค่ะชื่อธนาคารด้วยค่ะ');

        $this->assertSame(['celtic'], $svc->billedTier, 'ป้ายปุ่มคือ "ดู vip ส่วนตัว 99บาท" → ต้องออกบิล Celtic');
    }

    #[Test]
    public function a_private_matter_is_not_a_package_choice(): void
    {
        foreach (['เรื่องส่วนตัวค่ะ อยากปรึกษา', 'ขอถามส่วนตัวได้ไหมคะ'] as $text) {
            $svc = $this->service();
            $reading = $this->menuRow();

            $svc->tierChoice($reading, $text);

            $this->assertSame([], $svc->billedTier, "ห้ามออกบิล 99 ให้เองจากคำว่า \"{$text}\"");
        }
    }

    #[Test]
    public function saying_you_have_no_promptpay_never_gets_thai_bank_details_back(): void
    {
        $svc = $this->service();
        $reading = $this->menuRow();

        $result = $svc->tierChoice($reading, 'ไม่มีพร้อมเพย์ค่ะ');

        $this->assertNotSame('payment_info', $result['action']);
        $this->assertStringNotContainsString('2323775349', $result['message']);
    }

    #[Test]
    public function with_no_account_configured_the_package_menu_stays(): void
    {
        $svc = $this->service(withBank: false);
        $reading = $this->menuRow();

        $result = $svc->tierChoice($reading, 'ขอเลขบัญชีค่ะ');

        $this->assertSame(
            'tier_choice_invalid',
            $result['action'],
            'ไม่มีบัญชีให้โอน → ต้องคงกล่องเลือกแพคเกจ ไม่ใช่ตอบ "ทักทีมงาน" แล้ว flow ตัน'
        );
    }
}
