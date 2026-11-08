<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 🤍 ด่านเลือกแพคเกจ (tier_choice) ต้องไม่แนบเมนู 39/99 กับเรื่องวิกฤต (2026-11-09)
 *
 * เคสจริง prod #14660 (FB, 2026-11-08 18:52): ระหว่างเลือกแพคเกจ ลูกค้าพิมพ์
 *   "ถ้าเป็นคนอื่น…โดดน้ำหรือผูกคอหนือไม่ก็บ้าไปแล้วครับ"
 *   → ด่าน self-harm ไม่ติด ("โดดน้ำ/ผูกคอ" ต้องมี "ตาย" ต่อท้าย) → ได้ tier_choice_chitchat
 *     = คำตอบ AI + รายการแพคเกจ + ปุ่ม 39/99 (+ สติกเกอร์มือชี้)
 *
 * กฎเจ้าของ: เรื่องอยากทำร้ายตัวเอง = ปลอบเบา ๆ อย่างเดียว ไม่ไล่ไปโทรหาใคร ไม่สั่งสอน ไม่มีเมนูขาย
 */
class FortuneTierChoiceCrisisTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '30434167989505679';

    private const PROD_14660 = 'ถ้าเป็นคนอื่นขอโทษนะครับที่ผมใช้คำคำนี้ถ้าเป็นคนอื่นโดดน้ำหรือผูกคอหนือไม่ก็บ้าไปแล้วครับ';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();
        Http::fake(); // แจ้งแอดมินทาง Telegram — ห้ามยิงออกจริง
    }

    private function service(?string $aiReply = null): object
    {
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
            public array $billedTier = [];

            /** @var array<int, string|null> context ที่ส่งเข้า AI */
            public array $aiContexts = [];

            /** null = ทำตัวเหมือน AI ล้ม (คืน hint ที่ส่งมา) */
            public ?string $aiReply = null;

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

            protected function buildAIAssistedStepReminder(
                string $messageText,
                string $stepHint,
                ?array $userProfile = null,
                ?string $flowContext = null
            ): string {
                $this->aiContexts[] = $flowContext;

                return $this->aiReply === null ? $stepHint : $this->aiReply."\n\n".$stepHint;
            }
        };
        $svc->aiReply = $aiReply;
        $svc->setPlatform('facebook');

        return $svc;
    }

    private function menuRow(): FortuneReading
    {
        $r = new FortuneReading;
        $r->forceFill([
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID,
            'platform_user_id' => self::PSID,
            'facebook_user_name' => 'Watcharin',
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

    private function assertNoSalesMenu(string $message): void
    {
        foreach (['แพคเกจ', '39', '99', 'บาท', 'celtic', 'ยกเลิก', '1323', 'โทร', 'สายด่วน'] as $word) {
            $this->assertStringNotContainsString($word, $message, "คำตอบวิกฤตห้ามมี {$word}");
        }
    }

    #[Test]
    public function prod_14660_self_harm_during_package_choice_gets_comfort_without_the_menu(): void
    {
        $svc = $this->service();
        $reading = $this->menuRow();

        $result = $svc->tierChoice($reading, self::PROD_14660);

        $this->assertSame('crisis_support', $result['action'], 'ต้องไม่ใช่ tier_choice_chitchat (ซึ่งแนบเมนู + ปุ่ม 39/99)');
        $this->assertStringContainsString('อยู่ฟังตรงนี้', $result['message']);
        $this->assertNoSalesMenu($result['message']);
        $this->assertSame([], $svc->billedTier, 'ห้ามออกบิล');
        $this->assertSame(FortuneReading::STATUS_TIER_CHOICE, $reading->fresh()->conversation_status, 'ห้ามแตะสถานะ — เลือกแพคเกจต่อได้ทีหลัง');
    }

    #[Test]
    public function customer_can_still_pick_a_package_after_the_crisis_turn(): void
    {
        $svc = $this->service();
        $reading = $this->menuRow();

        $svc->tierChoice($reading, self::PROD_14660);
        $svc->tierChoice($reading->fresh(), '39');

        $this->assertSame(['deep'], $svc->billedTier);
    }

    #[Test]
    public function someone_elses_crisis_gets_a_caring_ai_reply_without_the_menu(): void
    {
        $svc = $this->service('ฟังแล้วห่วงทั้งคุณและเขาเลยค่ะ อยากเล่าต่อ พิมพ์มาได้เลยนะคะ');
        $reading = $this->menuRow();

        $result = $svc->tierChoice($reading, 'แฟนขู่จะฆ่าตัวตายถ้าเลิก ทำยังไงดีคะ');

        $this->assertSame('crisis_support', $result['action']);
        $this->assertSame(['crisis_care'], $svc->aiContexts, 'AI ต้องได้บริบทดูแลใจ ไม่ใช่บริบทเลือกแพคเกจ');
        $this->assertSame('ฟังแล้วห่วงทั้งคุณและเขาเลยค่ะ อยากเล่าต่อ พิมพ์มาได้เลยนะคะ', $result['message']);
        $this->assertNoSalesMenu($result['message']);
        $this->assertSame([], $svc->billedTier);
    }

    #[Test]
    public function someone_elses_crisis_falls_back_to_comfort_when_ai_slips_into_selling_or_fails(): void
    {
        $reading = $this->menuRow();

        $selling = $this->service('เข้าใจค่ะ ลองดูแพคเกจ 99 บาทไหมคะ');
        $slipped = $selling->tierChoice($reading, 'เพื่อนอยากฆ่าตัวตาย ทำยังไงดีคะ');
        $this->assertSame('crisis_support', $slipped['action']);
        $this->assertStringContainsString('อยู่ฟังตรงนี้', $slipped['message']);
        $this->assertNoSalesMenu($slipped['message']);

        $failed = $this->service(null)->tierChoice($reading, 'เพื่อนอยากฆ่าตัวตาย ทำยังไงดีคะ');
        $this->assertSame('crisis_support', $failed['action']);
        $this->assertStringContainsString('อยู่ฟังตรงนี้', $failed['message']);
    }

    #[Test]
    public function ordinary_chitchat_still_gets_the_package_reminder(): void
    {
        $svc = $this->service('ได้เลยค่ะ ไม่รีบนะคะ');
        $reading = $this->menuRow();

        $result = $svc->tierChoice($reading, 'ผมหลายเรื่องมากครับแต่ผมยังไม่สดวกตอนนี้ครับแม่');

        $this->assertNotSame('crisis_support', $result['action']);
        $this->assertNotContains('crisis_care', $svc->aiContexts);
    }
}
