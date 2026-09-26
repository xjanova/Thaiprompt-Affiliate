<?php

namespace Tests\Feature;

use App\Jobs\ProcessBufferedCelticMessageJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\CelticCrossService;
use App\Services\Fortune\CelticCrossConversationTrait;
use App\Services\Fortune\QaSettleTrait;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 🐢 Celtic 99฿ ตอบช้า — เคสจริง reading 13746 (FTU-260926-S8307, 2026-09-26)
 *
 * owner: "บอทตอบทำนาย ช้ามาก สำหรับ 99" — ลูกค้ารอ 70-80 วิ/ข้อ (ปกติ ~20-25) จาก 2 ต้นเหตุในโค้ด:
 *
 * 1) กดปุ่มคำถามแนะนำ "2" ถูกนับเป็น "เล่ายาว" → รอ 50 วิก่อน AI เริ่มคิดทุกข้อ + โดนสั่งตอบสั้น
 * 2) AI ช้า 126 วิ → ด่านกู้ที่ตัดสิน "ค้าง" ด้วยเวลา 90 วิ ตีตัวที่ยังคิดอยู่ว่าตาย → ปั่นซ้อน
 *    → ลูกค้าได้คำตอบข้อเดียวกัน 2 รอบคนละเนื้อ (18:30:23 + 18:32:05)
 *
 * ⚠️ ไม่ใช้ RefreshDatabase — FortuneReading ไม่ save + cache store = array (phpunit.xml)
 */
class CelticSlowAnswerIsNotStuckTest extends TestCase
{
    private object $settle;

    protected function setUp(): void
    {
        parent::setUp();

        // 🧪 prime static memo ของ FortuneTellingSetting::getSettings() → ไม่ต้องมี MySQL
        //   ตั้ง attribute ตรง ๆ ไม่ผ่าน mass-assign (แพตเทิร์นเดียวกับ QaSettleWindowDeadlineTest)
        $settings = new FortuneTellingSetting;
        $settings->celtic_cross_qa_window_minutes = 30;
        $settings->qa_settle_ramble_seconds = 50;
        $settings->qa_ramble_brief_reply = true;

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

        // ใช้ QaSettleTrait ตัวจริงทั้งหมด (ไม่ override qaIsRambling) — ต้องการเห็นสตรีคจริง
        $this->settle = new class($settings)
        {
            use QaSettleTrait;

            public function __construct(public $settings) {}
        };

        Cache::flush();
    }

    /**
     * บิลที่ setConversationState() เก็บในหน่วยความจำ (ของจริงเรียก update() ซึ่งไม่ทำอะไรกับแถวที่ไม่ save)
     */
    private function readingWithState(array $state): FortuneReading
    {
        $reading = new class extends FortuneReading
        {
            public int $stateWrites = 0;

            public function setConversationState(string $key, $value): void
            {
                $state = $this->conversation_state ?? [];
                $state[$key] = $value;
                $this->conversation_state = $state;   // หน่วยความจำเท่านั้น — ไม่แตะ DB
                $this->stateWrites++;
            }
        };
        $reading->id = 13746;
        $reading->conversation_state = $state;

        return $reading;
    }

    // ══════════════════════════════════════════════════════════════════════
    // 1) ปุ่มคำถามแนะนำ ≠ เล่ายาว
    // ══════════════════════════════════════════════════════════════════════

    /**
     * 🎯 เคสต้นเหตุ — กดปุ่ม "2" หลังคำตอบ 1,093 ตัว 57 วิ ขณะสตรีคติดไปแล้ว 2
     *
     * เดิม: รอ 50 วิ + สั่ง AI ตอบสั้น 2-3 บรรทัด · ต้องเป็น: หน้าต่างพื้นฐาน + คำทำนายเต็ม
     */
    public function test_กดปุ่มคำถามแนะนำ_ล้างสตรีค_ได้หน้าต่างพื้นฐานและคำตอบเต็ม(): void
    {
        $reading = $this->readingWithState([
            'qa_ramble_streak' => 2,
            'qa_answer_at' => now()->subSeconds(57)->toIso8601String(),
            'qa_answer_len' => 1093,
        ]);

        // ก่อนแก้ = สภาพที่ลูกค้าเจอจริง
        $this->assertTrue($this->settle->qaIsRambling($reading));
        $this->assertSame(50, $this->settle->qaSettleWindow($reading, 10));
        $this->assertNotSame('', $this->settle->qaBriefReplyDirective($reading), 'สตรีค 2 = โดนสั่งตอบสั้น');

        $this->settle->qaResetRamble($reading);

        $this->assertSame(0, $reading->getConversationState('qa_ramble_streak'));
        $this->assertFalse($this->settle->qaIsRambling($reading));
        $this->assertSame(10, $this->settle->qaSettleWindow($reading, 10), 'ต้องรอแค่หน้าต่างพื้นฐาน');
        $this->assertSame('', $this->settle->qaBriefReplyDirective($reading), 'คำถามจริงต้องได้คำทำนายเต็ม');
    }

    /** สตรีคเป็น 0 อยู่แล้ว → ห้ามเขียน state เปล่า ๆ (setConversationState เขียน JSON ทั้งก้อน) */
    public function test_สตรีคเป็นศูนย์อยู่แล้ว_ไม่เขียน_state(): void
    {
        $reading = $this->readingWithState(['qa_ramble_streak' => 0]);

        $this->settle->qaResetRamble($reading);

        $this->assertSame(0, $reading->stateWrites);
    }

    /**
     * 🛡️ กัน regression — พิมพ์เองเร็วกว่าเวลาอ่าน ยังต้องเข้าโหมดเล่ายาวเหมือนเดิม
     *
     * (เคสต้นทางของกลไกนี้ FTU-260902-V9628: เล่าเรื่องเป็นชิ้น ๆ ห่างกัน 15-47 วิ)
     */
    public function test_พิมพ์เองเร็วกว่าเวลาอ่าน_ยังนับสตรีคตามเดิม(): void
    {
        $reading = $this->readingWithState([
            'qa_answer_at' => now()->subSeconds(57)->toIso8601String(),
            'qa_answer_len' => 1093,
        ]);

        $this->assertSame(1, $this->settle->qaTrackRamble($reading));
        $this->assertSame(50, $this->settle->qaSettleWindow($reading, 10));
    }

    // ══════════════════════════════════════════════════════════════════════
    // 2) AI ช้า ≠ ตาย — ธง in-flight
    // ══════════════════════════════════════════════════════════════════════

    public function test_ธงกำลังคิด_ปักแล้วเห็น_ปลดแล้วหาย(): void
    {
        $this->assertFalse(CelticCrossService::isGenerationInFlight(13746));

        $token = CelticCrossService::markGenerationInFlight(13746);

        $this->assertNotNull($token);
        $this->assertTrue(CelticCrossService::isGenerationInFlight(13746));
        $this->assertFalse(CelticCrossService::isGenerationInFlight(13747), 'ธงต้องแยกรายบิล');

        CelticCrossService::clearGenerationInFlight(13746, $token);

        $this->assertFalse(CelticCrossService::isGenerationInFlight(13746));
    }

    /** 2 เส้นทางคิดบิลเดียวกันซ้อน — ตัวที่จบก่อนต้องไม่ปลดธงของอีกตัว */
    public function test_ปลดธงด้วย_token_ของคนอื่น_ต้องไม่ลบธง(): void
    {
        $first = CelticCrossService::markGenerationInFlight(13746);
        $second = CelticCrossService::markGenerationInFlight(13746);

        CelticCrossService::clearGenerationInFlight(13746, $first);
        $this->assertTrue(CelticCrossService::isGenerationInFlight(13746), 'ตัวที่ 2 ยังคิดอยู่');

        CelticCrossService::clearGenerationInFlight(13746, $second);
        $this->assertFalse(CelticCrossService::isGenerationInFlight(13746));

        // ปักไม่สำเร็จ (token null) → ปลดต้องไม่ทำอะไร ไม่ throw
        CelticCrossService::clearGenerationInFlight(13746, null);
        $this->assertFalse(CelticCrossService::isGenerationInFlight(13746));
    }

    /**
     * ⏱️ อายุธงต้องยาวกว่าเวลาที่ job รอ AI ได้ — ไม่งั้นธงหมดอายุก่อน AI ตอบ แล้วด่านกู้ปั่นซ้อนเหมือนเดิม
     */
    public function test_อายุธง_ต้องยาวกว่า_timeout_ของ_job(): void
    {
        $jobTimeout = (new \ReflectionClass(ProcessBufferedCelticMessageJob::class))
            ->getProperty('timeout')
            ->getDefaultValue();

        $this->assertGreaterThan($jobTimeout, CelticCrossService::GENERATION_INFLIGHT_TTL_SEC);
    }

    /**
     * 🎯 ด่านตอนลูกค้าพิมพ์ระหว่าง AI คิด — ค้างนาน 120 วิ แต่ AI ยังคิดอยู่ → ห้ามบอกให้พิมพ์ใหม่
     *
     * เดิม: เด้งสถานะ + ตอบ "รบกวนพิมพ์คำถามมาใหม่" → ลูกค้าถามซ้อน → ได้คำตอบ 2 รอบ
     */
    public function test_ค้างนานแต่เอไอยังคิดอยู่_ต้องบอกให้รอ_ไม่ใช่ให้พิมพ์ใหม่(): void
    {
        $handler = new class
        {
            use CelticCrossConversationTrait;

            public function generating(FortuneReading $reading): array
            {
                return $this->handleCelticGenerating($reading);
            }
        };

        $reading = new FortuneReading;   // ไม่ save → update() ไม่แตะ DB
        $reading->id = 13746;
        $reading->conversation_status = FortuneReading::STATUS_CELTIC_GENERATING;
        $reading->updated_at = now()->subSeconds(120);

        $token = CelticCrossService::markGenerationInFlight(13746);
        $this->assertSame('celtic_processing', $handler->generating($reading)['action']);

        // ธงหาย (process ตายจริง/คิดเสร็จแล้ว) → กู้ตามเกณฑ์เวลาเดิม
        CelticCrossService::clearGenerationInFlight(13746, $token);
        $this->assertSame('celtic_stuck_recovered', $handler->generating($reading)['action']);
    }
}
