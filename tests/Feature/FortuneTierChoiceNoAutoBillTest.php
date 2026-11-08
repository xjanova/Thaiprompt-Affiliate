<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 🛒 ด่านเลือกแพคเกจต้องออกบิลเฉพาะตอนลูกค้า "เลือก" — ไม่ใช่ตอนประโยคมีคำว่า 39 / ละเอียด (2026-11-09)
 *
 * เจ้าของ: "ทำไมดูฟรีเสร็จเปิดบิล 39 ให้เอง เฉยเลย"
 * เคสจริง (FB · การ์ดเลือกแพคเกจ tier_choice · ไม่มีใบไหนจ่าย):
 *   #14558 FTU-261030-B3883  "…เดี๋ยวให้หนูกลับบ้านแล้วหนูจะดูให้ลูกทำให้ 39 บาทใช่ไหมคะเรื่องละ"
 *   #14605 FTU-261102-P4011  "ถ้าดูเกี่ยวกับลายละเอียดครูหมอ ต้องโปรไหนคะ"
 *   #14660 FTU-261108-T0522  "ผมจะดูกับแม่ครับแบบละเอียดและให้แมช่วยขอเวลาอีกไม่นานครับแม่"
 *     (ได้ไพ่ฟรีจากคอมเมนต์ตอนเช้า → เย็นบอก "ไว้คราวหน้า/ยังไม่สะดวก" 3 รอบ → บิล 39 โผล่ → ต้องพิมพ์ยกเลิกเอง)
 *
 * ล็อกไว้:
 *   1. ประโยคถาม/ผัดเวลาที่มีคำของแพคเกจ → ไม่ออกบิล (ทั้ง 39 และ 99) ได้คำตอบ AI แทน
 *   2. คนที่เลือกจริง ("39", "ขอดูแบบละเอียดค่ะ", "เอา 99 ค่ะ") → ออกบิลทันทีเหมือนเดิม
 *   3. เลขที่ไม่ใช่ราคา (ปีเกิด 2539) → ไม่ใช่บิล 39
 */
class FortuneTierChoiceNoAutoBillTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '30434167989505679';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();
    }

    private function service(): object
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

            /** ตัด AI ออก — สนใจว่าเลือกสาขาไหน ไม่ใช่ถ้อยคำ */
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

    public static function notAPickProvider(): array
    {
        return [
            'prod #14558' => ['คนสองคนขอบคุณค่ะหนูอยู่ต่างจังหวัดหนูทำส่งไม่เป็นเดี๋ยวให้หนูกลับบ้านแล้วหนูจะดูให้ลูกทำให้ 39 บาทใช่ไหมคะเรื่องละ'],
            'prod #14605' => ['ถ้าดูเกี่ยวกับลายละเอียดครูหมอ ต้องโปรไหนคะ'],
            'prod #14660' => ['ผมจะดูกับแม่ครับแบบละเอียดและให้แมช่วยขอเวลาอีกไม่นานครับแม่'],
            'ถามเทียบ 39 กับ 99' => ['39 กับ 99 ต่างกันยังไงคะ'],
            'ถามค่าบูชาครู (คำของ 99)' => ['ค่าบูชาครูเท่าไหร่คะ'],
            'ถามคุณไสย (คำของ 99)' => ['คุณไสยมีจริงไหมคะ'],
            'ปีเกิดมีเลข 39' => ['ผมเกิด 12/3/2539 ครับ'],
            'ขอรายละเอียด' => ['ขอรายละเอียดหน่อยครับ'],
        ];
    }

    #[Test]
    #[DataProvider('notAPickProvider')]
    public function a_question_or_deferral_never_opens_a_bill(string $text): void
    {
        $svc = $this->service();
        $reading = $this->menuRow();

        $result = $svc->tierChoice($reading, $text);

        $this->assertSame([], $svc->billedTier, "ห้ามออกบิลให้เองจาก \"{$text}\"");
        $this->assertNotContains($result['action'], ['pending_payment', 'celtic_pending_payment']);
        $this->assertSame(
            FortuneReading::STATUS_TIER_CHOICE,
            $reading->fresh()->conversation_status,
            'ยังอยู่ขั้นเลือกแพคเกจ — ลูกค้าเลือกเองได้ต่อ'
        );
        // ⚠️ ไม่เช็ค bill_reference ว่าง — แถวเมนูได้เลขบิลตั้งแต่สร้าง (prod #14651-#14658 ก็มี)
        //    ตัวชี้ว่า "ออกบิล" คือการเข้า routePayFirstDeep/startCelticCrossFlow (billedTier) เท่านั้น
    }

    public static function realPickProvider(): array
    {
        return [
            'พิมพ์ 39' => ['39', 'deep'],
            'ขอดูแบบละเอียดค่ะ' => ['ขอดูแบบละเอียดค่ะ', 'deep'],
            'ขอ 39 ได้ไหมคะ' => ['ขอ 39 ได้ไหมคะ', 'deep'],
            'เอาแบบ 39 + เล่าเรื่อง' => ['เอาแบบ 39 ค่ะ อยากดูเรื่องความรัก', 'deep'],
            'ขอดู 39 เกี่ยวกับความรัก' => ['ขอดู 39 เกี่ยวกับความรักค่ะ', 'deep'],
            'เอา 99 ค่ะ' => ['เอา 99 ค่ะ', 'celtic'],
            'ป้ายปุ่ม vip 99' => ['ดู vip ส่วนตัว 99บาท', 'celtic'],
            'ดูคุณไสย' => ['ดูคุณไสย', 'celtic'],
        ];
    }

    #[Test]
    #[DataProvider('realPickProvider')]
    public function a_real_pick_still_bills_straight_away(string $text, string $tier): void
    {
        $svc = $this->service();
        $reading = $this->menuRow();

        $svc->tierChoice($reading, $text);

        $this->assertSame([$tier], $svc->billedTier, "\"{$text}\" = เลือก {$tier} แล้ว ต้องออกบิลทันที");
    }
}
