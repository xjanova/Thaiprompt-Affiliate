<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\QaSettleTrait;
use Tests\TestCase;

/**
 * ⏳ settle-buffer ต้อง "ยิงทันหน้าต่างคุย" — ไม่งั้นคำถามลูกค้าหายเงียบ
 *
 * เคสจริง FTU-260905-N3337 (reading 12386, ปราณี 2026-09-05):
 *   21:37:29  ตอบคำถามที่ 7 · log remaining_min = 2   ⇒ หน้าต่างหมด ~21:39:29
 *   21:38:37  ลูกค้ากดปุ่มคำถามแนะนำข้อ 2 → settle-buffer 50 วิ (rambling)
 *   21:39:30  ProcessBufferedCelticMessageJob: session expired → skip   ⇐ ช้าไป 1 วินาที
 *   ⇒ ลูกค้าจ่าย 99 กดปุ่มที่ *ระบบเสนอเอง* แล้วได้ความเงียบ ไม่มีข้อความบอกว่าทำไม
 *
 * ต้นเหตุ: **นาฬิกาสองตัวไม่คุยกัน** — settle_sec (debounce) กับหน้าต่างคุย
 * ด่าน "หมดเวลา" อยู่ที่ job ตอน flush ซึ่งสายเกินจะตัดสินใจอะไรได้แล้ว
 * ⇒ ต้องหดหน้าต่างรอตอน **เข้าคิว**
 *
 * ⚠️ ไม่ใช้ RefreshDatabase — ใช้ FortuneReading ที่ยังไม่ save (exists=false)
 *   ⇒ setConversationState() → update() คืน false ทันที ไม่แตะ DB
 *   (อ่าน state อย่างเดียว ไม่มีความเสี่ยง clobber ตามกับดัก 2026-09-01)
 */
class QaSettleWindowDeadlineTest extends TestCase
{
    private object $subject;

    private int $windowMin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->windowMin = 32;

        // 🧪 prime static memo ของ FortuneTellingSetting::getSettings() → ไม่ต้องมี MySQL
        //   (qaRemainingSeconds() เรียก getSettings() เหมือน canAskMoreCeltic() ตัวจริง)
        //   ตั้ง attribute ตรง ๆ ไม่ผ่าน mass-assign — กัน fillable กินค่าที่ตั้งไว้เงียบ ๆ
        $settings = new FortuneTellingSetting;
        $settings->celtic_cross_qa_window_minutes = $this->windowMin;
        $settings->qa_settle_ramble_seconds = 50;

        $ref = new \ReflectionClass(FortuneTellingSetting::class);
        foreach ([
            'cachedInstance' => $settings,
            'cachedPageId' => \App\Services\Fortune\FortunePageContext::currentId(),
            'cachedAt' => microtime(true),
        ] as $name => $value) {
            $prop = $ref->getProperty($name);
            $prop->setAccessible(true);
            $prop->setValue(null, $value);
        }

        $this->subject = new class($settings)
        {
            use QaSettleTrait;

            public bool $rambling = false;

            public function __construct(public $settings) {}

            // บังคับสถานะ "เล่ายาว" ตรง ๆ — ไม่ต้องปั่น qa_ramble_streak ผ่าน state
            public function qaIsRambling(FortuneReading $reading): bool
            {
                return $this->rambling;
            }
        };
    }

    /** บิล Celtic ที่ตอบคำถามแรกไปแล้ว $ago วินาที (= นาฬิกาเดินแล้ว) */
    private function readingAnsweredSecondsAgo(int $ago): FortuneReading
    {
        $reading = new FortuneReading;   // ไม่ save → ไม่แตะ DB
        $reading->id = 12386;
        $reading->celtic_first_answered_at = now()->subSeconds($ago);

        return $reading;
    }

    /** เหลือเวลาอีกกี่วินาทีในหน้าต่างคุย → คืนบิลที่อยู่ในสภาพนั้น */
    private function readingWithRemaining(int $remainingSec): FortuneReading
    {
        return $this->readingAnsweredSecondsAgo($this->windowMin * 60 - $remainingSec);
    }

    /**
     * 🎯 เคสต้นเหตุ — เหลือ 52 วินาที แต่หน้าต่างรอ 50 วินาที
     *
     * เดิม: รอ 50 → job ตื่น ~51 วิ → เลยเส้นตาย → skip → คำถามหาย
     * ต้องเป็น: หดให้ flush ทันก่อนเส้นตาย
     */
    public function test_เหลือเวลาน้อยกว่าหน้าต่างรอ_ต้องหดหน้าต่าง(): void
    {
        $this->subject->rambling = true;
        $reading = $this->readingWithRemaining(52);

        $window = $this->subject->qaSettleWindow($reading, 10);

        $this->assertLessThan(52, $window, 'ต้องหดให้สั้นกว่าเวลาที่เหลือ');
        $this->assertLessThanOrEqual(
            52 - 20,
            $window,
            'ต้องเผื่อกันชน 20 วิ ให้ job ตื่นทัน — flush ต้องเกิดก่อนเส้นตาย ไม่ใช่พอดีเป๊ะ'
        );
    }

    /**
     * 🚨 เหลือเวลาน้อยมาก → ต้องคืน 0 = ห้าม buffer ให้ตอบทันที
     *
     * caller ทั้ง 2 เลนเช็ค `$settleSec > 0` ก่อนเข้า buffer
     */
    public function test_เหลือเวลาน้อยมาก_ต้องคืนศูนย์เพื่อให้ตอบทันที(): void
    {
        $this->subject->rambling = true;

        foreach ([1, 5, 15, 20] as $เหลือ) {
            $this->assertSame(
                0,
                $this->subject->qaSettleWindow($this->readingWithRemaining($เหลือ), 10),
                "เหลือ {$เหลือ} วิ ต้องยิงทันที ห้าม buffer"
            );
        }
    }

    /**
     * ✅ เวลาเหลือเยอะ → ต้องไม่แตะอะไรเลย (กัน regression กับ debounce ปกติ)
     */
    public function test_เวลาเหลือเยอะ_หน้าต่างต้องเท่าเดิม(): void
    {
        $reading = $this->readingWithRemaining(600); // เหลือ 10 นาที

        $this->subject->rambling = false;
        $this->assertSame(10, $this->subject->qaSettleWindow($reading, 10), 'ไม่ rambling → base เดิม');

        $this->subject->rambling = true;
        $this->assertSame(50, $this->subject->qaSettleWindow($reading, 10), 'rambling → ขยายเป็น 50 ตามเดิม');
    }

    /**
     * 🆕 ยังไม่เริ่มจับเวลา (ยังไม่ตอบคำถามแรก) → ไม่มีเส้นตาย ห้ามหด
     *
     * ถ้าหดตรงนี้ = ลูกค้าที่เพิ่งเปิดบิลจะไม่ได้ debounce เลย
     */
    public function test_ยังไม่เริ่มจับเวลา_ต้องไม่หด(): void
    {
        $reading = new FortuneReading;
        $reading->id = 12386;
        // ไม่มี celtic_first_answered_at และไม่มี pro_session_started_at

        $this->assertNull($reading->qaRemainingSeconds(), 'ไม่มีเส้นตาย = null');

        $this->subject->rambling = true;
        $this->assertSame(50, $this->subject->qaSettleWindow($reading, 10));
    }

    /**
     * ⏱️ เส้นตายของ Pro Session ต้องถูกนับด้วย — บิลเดียวติดได้ทั้ง 2 เลน
     *
     * Celtic เปิด ProSession ต่อ ⇒ ตัวที่หมดก่อนคือตัวที่ตัดจริง
     * ถ้าดูแค่เลน Celtic เลนเดียว จะพลาดเคสที่ ProSession หมดก่อน
     */
    public function test_เส้นตาย_prosession_ที่หมดก่อน_ต้องเป็นตัวตัด(): void
    {
        $reading = $this->readingWithRemaining(3600);      // เลน Celtic เหลือเยอะ
        $reading->conversation_state = [
            'pro_session_started_at' => now()->subMinutes(30)->toIso8601String(),
            'pro_session_window_minutes' => 30,            // ⇒ ProSession หมดพอดี
        ];

        $this->assertSame(0, $reading->qaRemainingSeconds(), 'ต้องเอาเส้นตายที่หมดก่อน');

        $this->subject->rambling = true;
        $this->assertSame(0, $this->subject->qaSettleWindow($reading, 10), 'ProSession หมด → ยิงทันที');
    }

    /** หมดเวลาไปแล้ว → ต้องไม่คืนค่าติดลบ */
    public function test_เลยเส้นตายไปแล้ว_ต้องไม่ติดลบ(): void
    {
        $reading = $this->readingAnsweredSecondsAgo($this->windowMin * 60 + 300);

        $this->assertSame(0, $reading->qaRemainingSeconds());

        $this->subject->rambling = true;
        $this->assertSame(0, $this->subject->qaSettleWindow($reading, 10));
    }
}
