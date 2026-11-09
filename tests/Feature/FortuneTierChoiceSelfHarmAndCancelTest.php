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
 * 🤍 (2026-11-09 owner) เลิกตรวจจับ self-harm/วิกฤตแล้ว — "ให้ AI แม่หมอตอบตามปกติ"
 *   ระหว่างเลือกแพคเกจ (tier_choice) ข้อความแบบ "อยากตาย ไม่ไหวแล้ว" ไปเส้นคุยปกติ (tier_choice_chitchat)
 *   ไม่มี crisis_support / บริบท crisis_care อีกแล้ว
 *
 * 🙏 (2026-11-09 owner) "ยกเลิกก็ยกเลิกแค่นั้น" — ข้อความยกเลิกบรรทัดเดียว ไม่มีข้อคิด ไม่ชวนซื้อ
 */
class FortuneTierChoiceSelfHarmAndCancelTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '30434167989505679';

    /** คำที่ข้อความยกเลิกต้องไม่มี (ข้อคิดนักปราชญ์เดิม / ชวนดูดวงใหม่ / ชวนซื้อ) */
    private const PITCH_WORDS = ['ดูดวง', 'คนเก่ง', 'ความหวัง', 'พร้อมเมื่อไหร่', 'บาท', '39', '99', 'แพคเกจ', 'แม่หมอ', 'juntra'];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();
        Http::fake(); // กันยิงออกนอกเครื่องจริง
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

            /** @var array<int, string> ข้อความลูกค้าที่ส่งเข้า AI */
            public array $aiInputs = [];

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

            // AI ถูก stub — จดว่าถูกเรียกด้วยบริบทอะไร
            protected function buildAIAssistedStepReminder(
                string $messageText,
                string $stepHint,
                ?array $userProfile = null,
                ?string $flowContext = null
            ): string {
                $this->aiContexts[] = $flowContext;
                $this->aiInputs[] = $messageText;

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

    #[Test]
    public function self_harm_text_during_package_choice_goes_to_the_normal_ai_reply(): void
    {
        $svc = $this->service('แม่หมอฟังอยู่นะคะ เล่าให้ฟังได้เลยค่ะ');
        $reading = $this->menuRow();

        $result = $svc->tierChoice($reading, 'อยากตาย ไม่ไหวแล้ว');

        $this->assertSame('tier_choice_chitchat', $result['action'], 'ต้องไปเส้น AI ตอบปกติ ไม่ใช่ crisis_support');
        $this->assertSame(['tier_choice'], $svc->aiContexts, 'AI ได้บริบทเลือกแพคเกจตามปกติ ไม่มี crisis_care');
        $this->assertSame(['อยากตาย ไม่ไหวแล้ว'], $svc->aiInputs);
        $this->assertStringStartsWith('แม่หมอฟังอยู่นะคะ เล่าให้ฟังได้เลยค่ะ', $result['message']);
        $this->assertSame([], $svc->billedTier, 'ห้ามออกบิล');
        $this->assertSame(FortuneReading::STATUS_TIER_CHOICE, $reading->fresh()->conversation_status);
    }

    #[Test]
    public function the_prod_14660_text_is_also_a_normal_chitchat_turn(): void
    {
        $svc = $this->service('เข้าใจเลยค่ะ');
        $reading = $this->menuRow();

        $result = $svc->tierChoice($reading, 'ถ้าเป็นคนอื่นขอโทษนะครับที่ผมใช้คำคำนี้ถ้าเป็นคนอื่นโดดน้ำหรือผูกคอหนือไม่ก็บ้าไปแล้วครับ');

        $this->assertSame('tier_choice_chitchat', $result['action']);
        $this->assertSame(['tier_choice'], $svc->aiContexts);
        $this->assertSame([], $svc->billedTier);
    }

    #[Test]
    public function typing_cancel_at_package_choice_only_says_it_is_cancelled(): void
    {
        $svc = $this->service();
        $reading = $this->menuRow();

        $result = $svc->tierChoice($reading, 'ยกเลิก');

        $this->assertSame('cancelled', $result['action']);
        $this->assertSame(FortuneReading::CANCEL_NOTICE, $result['message']);
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $reading->fresh()->conversation_status);
        $this->assertSame([], $svc->aiContexts, 'ยกเลิกไม่ต้องเรียก AI');
    }

    #[Test]
    public function cancel_notices_are_one_neutral_line_without_a_pitch(): void
    {
        $withBill = new FortuneReading;
        $withBill->bill_reference = 'FTU-261109-AB123';
        $noBill = new FortuneReading;

        $notices = [
            FortuneReading::CANCEL_NOTICE,
            FortuneReading::buildCancelNotice($withBill),
            FortuneReading::buildCancelNotice($noBill),
            FortuneReading::buildCancelNotice($withBill, 'user_cancelled'),
            FortuneReading::buildCancelNotice($noBill, 'user_cancelled'),
        ];

        $this->assertSame('บิล FTU-261109-AB123 หมดเวลาชำระ ยกเลิกให้แล้วค่ะ 🙏', $notices[1]);
        $this->assertSame('ยกเลิกบิล FTU-261109-AB123 ให้แล้วค่ะ 🙏', $notices[3]);
        $this->assertSame(FortuneReading::CANCEL_NOTICE, $notices[4]);

        foreach ($notices as $text) {
            $this->assertStringNotContainsString("\n", $text, 'ต้องเป็นบรรทัดเดียว');
            $this->assertStringContainsString('ยกเลิก', $text);
            foreach (self::PITCH_WORDS as $word) {
                $this->assertStringNotContainsString($word, str_replace('FTU-261109-AB123', '', $text), "ข้อความยกเลิกห้ามมี {$word}");
            }
        }

        // 25 ข้อคิด "นักปราชญ์" เดิมถูกลบทิ้งแล้ว
        $this->assertFalse(method_exists(FortuneReading::class, 'buildCancelWakeupMessage'));
    }
}
