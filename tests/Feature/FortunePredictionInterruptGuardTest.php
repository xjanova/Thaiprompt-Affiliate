<?php

namespace Tests\Feature;

use App\Models\FortuneProductOffer;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\MarketplaceSetting;
use App\Services\Fortune\FortuneMuOfferService;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * ห้ามแทรกกลางคำทำนายที่ลูกค้าจ่ายเงินแล้ว (เคส FTU-260908-W7476, 2026-09-08)
 *
 * เคสจริงที่ทำให้ต้องมีเทสต์ชุดนี้ — บิล Deep 39฿ ช่วง "ถามต่อ 7 นาที":
 *   18:21 ลูกค้าตอบเรื่องค่าน้ำค่าไฟว่า "ตอนนี้ไม่อะไรเลยจ่ายไปแล้วละคับ"
 *         → ด่าน SlipOK อ่านคำว่า "จ่ายไปแล้ว" เป็นเคลมจ่ายค่าครู → บอท **ทวงสลิป**
 *           จากคนที่จ่ายเงินไปแล้ว แถมกลืนข้อความนั้นทิ้ง ไม่ถึงเส้นทำนาย
 *   18:23 ลูกค้าส่งรูปสลิปตามที่บอทสั่ง
 *         → เส้น gesture_image ยิง **การ์ดขายของเสริมดวง** แทรกกลางคำทำนาย
 *
 * กุญแจของทั้งสองบั๊กเป็นตัวเดียวกัน: ช่วงถามต่อมี `conversation_status = completed`
 * แล้ว (คำทำนายส่งไปแล้วจริง) แต่ลูกค้า **ยังทำนายไม่จบ** — ด่านที่ดูแค่ status
 * จึงเห็นเป็น "จบแล้ว" ทั้งที่ยังอยู่กลางบิลที่จ่ายเงินแล้ว
 *
 * @group fortune-prediction-guard
 */
class FortunePredictionInterruptGuardTest extends TestCase
{
    use RefreshDatabase;

    /** PSID สมมติของลูกค้าในเคสนี้ */
    private const PSID = '37397902136522528';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * สร้าง reading ทรงเดียวกับ FTU-260908-W7476 ตอนนาทีที่บั๊กเกิด
     *
     * @param  bool  $proSessionActive  true = ยังอยู่ในช่วงถามต่อ (ยังทำนายไม่จบ)
     */
    private function makePaidReading(bool $proSessionActive): FortuneReading
    {
        return FortuneReading::create([
            'facebook_user_id' => self::PSID,
            'platform' => 'facebook',
            'platform_user_id' => self::PSID,
            'reading_type' => 'deep',
            'is_paid' => true,
            'amount_paid' => 39.30,
            'paid_at' => now()->subMinutes(8),
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'conversation_state' => [
                'pro_session_active' => $proSessionActive,
                'pro_session_type' => 'deep',
                'pro_session_window_minutes' => 7,
            ],
            'bill_reference' => 'FTU-TEST-W7476',
        ]);
    }

    /** เปิดสวิตช์ใหญ่ของระบบเสนอสินค้า (ไม่งั้น canOffer ตกตั้งแต่ด่านแรก) */
    private function enableMuOffer(): void
    {
        MarketplaceSetting::set('fortune_mu_offer_enabled', '1', 'boolean');
        MarketplaceSetting::set('fortune_mu_offer_triggers', '', 'string');
    }

    // ── การ์ดขายของ ────────────────────────────────────────────────────

    /**
     * แกนของบั๊ก: จ่ายแล้ว + ยังอยู่ในช่วงถามต่อ → ห้ามยื่นการ์ดจากเส้น "ส่งรูป"
     */
    public function test_gesture_offer_blocked_while_paid_prediction_in_flight(): void
    {
        $this->enableMuOffer();
        $this->makePaidReading(proSessionActive: true);

        $this->assertFalse(
            app(FortuneMuOfferService::class)->canOffer(
                'facebook',
                self::PSID,
                FortuneProductOffer::TRIGGER_GESTURE_IMAGE
            ),
            'ลูกค้าจ่ายแล้วยังทำนายไม่จบ ต้องไม่ได้การ์ดขายของจากเส้นส่งรูป'
        );
    }

    /**
     * ทำนายจบจริง (ปิดช่วงถามต่อแล้ว) → การ์ดกลับมายิงได้เหมือนเดิม
     *
     * ด่านใหม่ต้องไม่กลายเป็นสวิตช์ปิดถาวรของเส้น gesture
     */
    public function test_gesture_offer_allowed_after_prediction_finished(): void
    {
        $this->enableMuOffer();
        $this->makePaidReading(proSessionActive: false);

        $this->assertTrue(
            app(FortuneMuOfferService::class)->canOffer(
                'facebook',
                self::PSID,
                FortuneProductOffer::TRIGGER_GESTURE_IMAGE
            ),
            'ทำนายจบแล้ว เส้น gesture ต้องยิงได้ตามเดิม'
        );
    }

    /**
     * คนที่ไม่มีบิลอะไรเลย = พฤติกรรมเดิมทุกประการ (กันงานนี้ไปกระทบคนทั่วไป)
     */
    public function test_gesture_offer_allowed_for_customer_with_no_reading(): void
    {
        $this->enableMuOffer();

        $this->assertTrue(
            app(FortuneMuOfferService::class)->canOffer(
                'facebook',
                self::PSID,
                FortuneProductOffer::TRIGGER_GESTURE_IMAGE
            ),
            'คนที่ไม่มีบิล ต้องไม่ถูกด่านใหม่กัน'
        );
    }

    /**
     * ยกเว้นที่ 1 — การ์ดท้ายบิล (deep_end/celtic_end)
     *
     * จุดนี้ยิงตอน "จบจริง" และมีด่านของตัวเองอยู่แล้ว (is_lingering /
     * deep_pro_session_timeout) ถ้าด่านใหม่กันด้วย การ์ดท้ายบิลจะดับทั้งเส้น
     * — ซึ่งเป็นนาทีที่ขายของได้ดีที่สุด
     */
    public function test_paid_end_triggers_are_exempt_from_the_guard(): void
    {
        $this->enableMuOffer();
        $this->makePaidReading(proSessionActive: true);

        $service = app(FortuneMuOfferService::class);

        foreach (FortuneProductOffer::PAID_END_TRIGGERS as $trigger) {
            $this->assertTrue(
                $service->canOffer('facebook', self::PSID, $trigger),
                "จุดยิงท้ายบิล [{$trigger}] ต้องไม่ถูกด่านใหม่กัน"
            );
        }
    }

    /**
     * ยกเว้นที่ 2 — ลูกค้าถามหาของเอง ต้องได้คำตอบเสมอ
     *
     * การ์ดทุกใบสัญญาไว้เองว่า "อยากได้อะไรบอกมา แม่หมอหาให้"
     */
    public function test_customer_ask_is_exempt_from_the_guard(): void
    {
        $this->enableMuOffer();
        $this->makePaidReading(proSessionActive: true);

        $this->assertTrue(
            app(FortuneMuOfferService::class)->canOffer(
                'facebook',
                self::PSID,
                FortuneProductOffer::TRIGGER_CUSTOMER_ASK
            ),
            'ลูกค้าถามหาของเอง ต้องได้คำตอบแม้กำลังทำนายอยู่'
        );
    }

    // ── ทวงสลิป ────────────────────────────────────────────────────────

    /**
     * ประโยคจริงจากเคส — มีคำว่า "จ่ายไปแล้ว" แต่พูดถึงค่าน้ำค่าไฟ ไม่ใช่ค่าครู
     *
     * ระหว่างช่วงถามต่อ ห้ามอ่านเป็นเคลมจ่ายเงิน และห้ามกลืนข้อความ
     * (คืน null = ปล่อยไหลต่อเข้าเส้นทำนาย)
     */
    public function test_slip_chase_skipped_while_paid_prediction_in_flight(): void
    {
        $settings = FortuneTellingSetting::getSettings();
        $settings->enable_slipok_verify = true;
        $settings->slipok_branch_id = 'test-branch';
        $settings->slipok_api_key = 'test-key';
        $settings->save();
        Cache::flush();

        $this->makePaidReading(proSessionActive: true);

        $service = new FortuneConversationService($settings->fresh());

        // ยืนยันก่อนว่าประโยคนี้ "ติดกับดัก" จริง — ไม่งั้นเทสต์ผ่านด้วยเหตุผลผิด
        $this->assertTrue(
            $service->looksLikePaidNotReceived('ตอนนี้ไม่อะไรเลยจ่ายไปแล้วละคับ'),
            'ประโยคนี้ต้องยังเข้าเงื่อนไข paid-claim อยู่ (ไม่งั้นเทสต์นี้ไม่ได้ทดสอบอะไร)'
        );

        $this->assertNull(
            $service->tryReturningPaidSlipCheck('facebook', self::PSID, 'ตอนนี้ไม่อะไรเลยจ่ายไปแล้วละคับ'),
            'กำลังทำนายอยู่ ต้องไม่ทวงสลิป และต้องปล่อยข้อความไหลต่อ'
        );
    }
}
