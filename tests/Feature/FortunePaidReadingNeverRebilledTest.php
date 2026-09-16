<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\UniquePaymentAmount;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 🚨 (2026-09-16) บิลที่จ่ายแล้ว ห้ามถูกออกบิลซ้ำตอนเปิดไพ่เสร็จ
 *
 * เคสจริง Patitta Nathamploy (FTU-260916-D8561 / R13284):
 *   11:35 โอน 39 ก่อนมีบิล + ส่งสลิป → auto-provision → provisionDeepFromVerifiedSlip → is_paid=1
 *   12:07 ลูกค้าพิมพ์วันเกิดกลับมา → เปิดไพ่ → afterTarotCardDrawn
 *         → ด่านเดิม `$payFirstMode && $isPaid` ไม่ผ่าน (เส้นสลิปไม่เคยตั้งธง pay_first_mode)
 *         → ตกสาขา legacy pay-after → **ออกบิล ฿39.42 + QR ทวงเงินคนที่จ่ายไปแล้ว**
 *         → status เด้งกลับ pending_payment → คำทำนายไม่เคยถูกสร้าง
 *   ลูกค้าถาม "ต้องจ่ายอีกรึค่ะ" → AI ยืนยันว่าใช่ (ผิด) → เจ้าของต้องเข้าไปแก้เอง
 *
 * เกณฑ์ที่ถูก: **`is_paid` อย่างเดียว** — จ่ายแล้วคือจ่ายแล้ว ไม่ว่าเงินจะเข้ามาทางไหน
 *
 * 🔧 วิธีเทสต์โดยไม่ยิงอะไรจริง (AI / Job / ธนาคาร / เน็ต) — สองเส้นแยกกันคนละกลไก:
 *   • เส้น "จ่ายแล้ว" → จอง dispatch lock ไว้ก่อน ⇒ return 'processing' ทันที ไม่ dispatch Job
 *     ⚠️ ต้องจอง **หลัง** สร้าง service เสมอ — service() เรียก Cache::flush() (ล้าง lock ที่จองไว้)
 *        รอบแรกจองก่อน → lock โดนล้าง → ไหลไป dispatch จริง ได้ 'deep_card_announced' (CI แดง)
 *   • เส้น legacy pay-after → override getActivePaymentMode()='both' ⇒ แค่ถามวิธีจ่าย
 *     (เก็บคำถาม + status=awaiting_payment_method) ยังไม่สร้างบิล/QR
 *
 * @group fortune-billing
 */
class FortunePaidReadingNeverRebilledTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '27466581493002956';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        // ⚠️ getSettings() มี static memo ข้ามเทสต์ใน process เดียวกัน
        FortuneTellingSetting::clearSettingsCache();
    }

    /**
     * @param  string|null  $forcePaymentMode  บังคับโหมดจ่ายเงิน ('both' = ถามวิธีจ่าย ไม่สร้างบิลจริง)
     */
    private function service(?string $forcePaymentMode = null): object
    {
        $settings = FortuneTellingSetting::getSettings();
        $settings->deep_reading_price = 39;
        $settings->save();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();

        return new class(FortuneTellingSetting::getSettings(), $forcePaymentMode) extends FortuneConversationService
        {
            public function __construct(FortuneTellingSetting $settings, private ?string $forcedMode)
            {
                parent::__construct($settings);
            }

            /** เปิดเมธอด protected ให้เทสต์เรียกตรง */
            public function callAfterTarotCardDrawn(FortuneReading $reading): array
            {
                return $this->afterTarotCardDrawn($reading, $reading->getCollectedQuestions(), 1);
            }

            /** กันเส้น legacy ไปสร้างบิล/QR จริงตอนเทสต์ */
            protected function getActivePaymentMode(): string
            {
                return $this->forcedMode ?? parent::getActivePaymentMode();
            }
        };
    }

    /** จองคิวทำนาย → เส้น "จ่ายแล้ว" จะคืน processing ทันที (ต้องเรียกหลังสร้าง service) */
    private function armDispatchLock(FortuneReading $reading): void
    {
        Cache::add("fortune:deep_dispatch:{$reading->id}", 1, 600);
    }

    /**
     * บิลที่พร้อมทำนาย: จ่ายแล้ว + มีวันเกิด + มีคำถาม + จับไพ่แล้ว
     */
    private function paidReadyReading(array $state = []): FortuneReading
    {
        $reading = FortuneReading::create([
            'facebook_user_id' => self::PSID,
            'facebook_user_name' => 'Patitta Nathamploy',
            'questions' => [],
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => FortuneReading::STATUS_COLLECTING_TAROT,
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
            'platform' => 'facebook',
            'platform_user_id' => self::PSID,
            'bill_reference' => FortuneReading::generateBillReference(),
            'birth_date' => '1974-07-12',
            'is_paid' => true,
            'paid_at' => now(),
            'amount_paid' => 39.00,
            // มีผังดวงแล้ว → ข้ามการวาดรูป (เทสต์ไม่ต้องยิงเรนเดอร์)
            'reading_image_url' => 'https://example.test/chart.png',
        ]);

        $reading->addQuestion('ขอดูพื้นดวงโดยรวมของเจ้าชะตา');
        foreach ($state as $k => $v) {
            $reading->setConversationState($k, $v);
        }

        return $reading->fresh();
    }

    /**
     * 1️⃣ เคสจริง — จ่ายผ่านสลิปโอนก่อนบิล (ไม่มีธง pay_first_mode) ต้องไปต่อที่การทำนาย
     *    ห้ามออกบิลใหม่ ห้ามเด้งกลับ pending_payment
     */
    public function test_บิลจ่ายแล้วแบบไม่มีธงpayfirst_ไม่ออกบิลซ้ำ(): void
    {
        $reading = $this->paidReadyReading([
            'prepay_provisional' => true,
            'prepay_package_locked' => 'deep',
        ]);
        $this->assertFalse((bool) $reading->getConversationState('pay_first_mode', false));

        $svc = $this->service();
        $this->armDispatchLock($reading);

        $result = $svc->callAfterTarotCardDrawn($reading);

        $this->assertSame('processing', $result['action'], 'บิลจ่ายแล้วต้องเข้าเส้นทำนาย ไม่ใช่เส้นออกบิล');

        $fresh = $reading->fresh();
        $this->assertNotSame(
            FortuneReading::STATUS_PENDING_PAYMENT,
            $fresh->conversation_status,
            'ห้ามเด้งกลับสถานะรอชำระเงิน — ลูกค้าจ่ายไปแล้ว'
        );
        $this->assertNull($fresh->unique_payment_amount_id, 'ห้ามผูกยอดโอนใหม่กับบิลที่จ่ายแล้ว');
        $this->assertEqualsWithDelta(39.00, (float) $fresh->amount_paid, 0.001, 'ห้ามทับยอดที่ลูกค้าจ่ายจริง');
        $this->assertSame(0, UniquePaymentAmount::where('transaction_id', $reading->id)->count(), 'ห้ามสร้างบิลใหม่');
    }

    /** 2️⃣ เส้น pay-first เดิม (มีธง) ต้องยังทำงานเหมือนเดิม — ไม่ถูกกระทบจากการแก้ */
    public function test_บิลpayfirstเดิม_ยังเข้าเส้นทำนายเหมือนเดิม(): void
    {
        $reading = $this->paidReadyReading(['pay_first_mode' => true]);

        $svc = $this->service();
        $this->armDispatchLock($reading);

        $result = $svc->callAfterTarotCardDrawn($reading);

        $this->assertSame('processing', $result['action']);
        $this->assertNull($reading->fresh()->unique_payment_amount_id);
    }

    /** 3️⃣ บิลที่ยังไม่จ่ายจริง ต้องยังไปเส้นเก็บเงินตามเดิม (ไม่ได้เปิดให้ทำนายฟรี) */
    public function test_บิลยังไม่จ่าย_ยังต้องไปเส้นเก็บเงินตามเดิม(): void
    {
        $reading = $this->paidReadyReading();
        // ⚠️ amount_paid เป็น NOT NULL ในสคีมา → ใช้ 0 ไม่ใช่ null
        $reading->forceFill(['is_paid' => false, 'paid_at' => null, 'amount_paid' => 0])->save();

        // mode=both → เส้น legacy หยุดที่ "ถามวิธีจ่าย" ไม่ไปสร้างบิล/QR จริง
        $svc = $this->service('both');
        $this->armDispatchLock($reading);

        $result = $svc->callAfterTarotCardDrawn($reading->fresh());

        $this->assertNotSame(
            'processing',
            $result['action'],
            'ยังไม่จ่าย = ต้องไม่หลุดเข้าเส้นทำนาย (ไม่งั้นแจกดวงฟรี)'
        );
        $this->assertSame(
            FortuneReading::STATUS_AWAITING_PAYMENT_METHOD,
            $reading->fresh()->conversation_status,
            'บิลที่ยังไม่จ่ายต้องเดินเข้าเส้นเก็บเงินตามเดิม'
        );
    }
}
