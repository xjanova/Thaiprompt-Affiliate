<?php

namespace Tests\Unit\Services;

use App\Models\FortuneReading;
use App\Services\FortuneConversationService;
use Carbon\Carbon;
use ReflectionClass;
use Tests\TestCase;

/**
 * 🤝 ทดสอบบรรทัดปิดท้ายหลังดูดวงจบ — ห้ามขายซ้ำในวันที่ลูกค้าเพิ่งจ่าย
 *
 * owner spec (2026-08-21): "เมื่อเพิ่งดูดวงไป ก็ไม่ควรขายซ้ำอีกภายในวันนั้น ๆ
 *   นอกจากเจ้าดวงมีเจตนาจะดูเพิ่ม ก็จับเจตนาเอา"
 *
 * เคสจริงที่จุดชนวน (FTU-260821-K9664): ลูกค้าจ่าย 39฿ ถาม 3 ข้อ ไม่ได้คำตอบเลย
 *   แล้วปิดท้ายด้วย '🔮 อยากให้แม่หมอดูใหม่ — พิมพ์ "ดูดวง" ได้เสมอนะคะ'
 *   = ยื่นขายรอบใหม่ต่อหน้าคนที่เพิ่งจ่ายไปและยังไม่ได้ของ
 *
 * ⚠️ นับเฉพาะบิลที่ **จ่ายเงิน** (owner ยืนยัน) — ดวงฟรีรายวันยังชวนต่อได้ตามปกติ
 *   เพราะกล่องฟรีมีหางคำชวน + ปุ่ม VIP เป็นสเปกเดิมที่ owner สั่งไว้
 *
 * ไม่ต้องใช้ฐานข้อมูล — เรียกเมธอดใน trait ผ่าน Reflection + โมเดลที่ยังไม่ save
 */
class FortuneClosingInviteTest extends TestCase
{
    protected FortuneConversationService $service;

    /** ชิ้นส่วนที่แปลว่า "กำลังยื่นขายรอบใหม่" */
    private const SELL_CTA = 'ดูดวง';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 8, 21, 19, 51, 0));

        $this->service = (new ReflectionClass(FortuneConversationService::class))
            ->newInstanceWithoutConstructor();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function invokeTrait(string $method, ...$args)
    {
        $ref = new \ReflectionMethod(FortuneConversationService::class, $method);
        $ref->setAccessible(true);

        return $ref->invoke($this->service, ...$args);
    }

    protected function reading(bool $isPaid, ?Carbon $paidAt, ?Carbon $createdAt = null): FortuneReading
    {
        $r = new FortuneReading;
        $r->is_paid = $isPaid;
        $r->paid_at = $paidAt;
        $r->created_at = $createdAt ?: Carbon::create(2026, 8, 21, 19, 25, 0);

        return $r;
    }

    /** เพิ่งจ่ายวันนี้ → ห้ามมีคำชวน "ดูดวง" รอบใหม่ */
    public function test_no_sell_cta_when_paid_today(): void
    {
        $reading = $this->reading(true, Carbon::create(2026, 8, 21, 19, 29, 39));

        $this->assertTrue($this->invokeTrait('justHadPaidReadingToday', $reading));

        $line = $this->invokeTrait('closingInviteLine', $reading, false);
        $this->assertStringNotContainsString(self::SELL_CTA, $line);
        $this->assertStringContainsString('อ่านคำทำนายล่าสุด', $line);
    }

    /** เพิ่งจ่ายวันนี้ + ผู้เรียกพิมพ์บรรทัด "อ่านคำทำนายล่าสุด" ไปแล้ว → ห้ามซ้ำบรรทัดเดิม */
    public function test_no_duplicate_recall_line_when_already_shown(): void
    {
        $reading = $this->reading(true, Carbon::create(2026, 8, 21, 19, 29, 39));

        $line = $this->invokeTrait('closingInviteLine', $reading, true);

        $this->assertStringNotContainsString(self::SELL_CTA, $line);
        $this->assertStringNotContainsString('อ่านคำทำนายล่าสุด', $line);
        $this->assertNotSame('', trim($line)); // ต้องไม่ทิ้งกล่องห้วน ๆ
    }

    /** จ่ายเมื่อวาน → กลับมาชวนได้ตามปกติ (กฎคุมแค่ "ภายในวันนั้น ๆ") */
    public function test_sell_cta_returns_next_day(): void
    {
        $reading = $this->reading(true, Carbon::create(2026, 8, 20, 23, 55, 0));

        $this->assertFalse($this->invokeTrait('justHadPaidReadingToday', $reading));
        $this->assertStringContainsString(self::SELL_CTA, $this->invokeTrait('closingInviteLine', $reading, false));
    }

    /** ยังไม่จ่าย → ไม่เข้าเงื่อนไข ชวนได้ปกติ (ดวงฟรีต้องไม่โดนกฎนี้) */
    public function test_sell_cta_stays_for_unpaid_reading(): void
    {
        $reading = $this->reading(false, null, Carbon::create(2026, 8, 21, 19, 0, 0));

        $this->assertFalse($this->invokeTrait('justHadPaidReadingToday', $reading));
        $this->assertStringContainsString(self::SELL_CTA, $this->invokeTrait('closingInviteLine', $reading, false));
    }

    /**
     * จ่ายแล้วแต่ `paid_at` ว่าง (บิลเก่า/ตัดมือ) → ใช้ `created_at` แทน
     * ⚠️ ไม่งั้นบิลที่ไม่มี paid_at จะหลุดกฎแล้วโดนขายซ้ำทันที
     */
    public function test_falls_back_to_created_at_when_paid_at_missing(): void
    {
        $reading = $this->reading(true, null, Carbon::create(2026, 8, 21, 10, 0, 0));

        $this->assertTrue($this->invokeTrait('justHadPaidReadingToday', $reading));
        $this->assertStringNotContainsString(self::SELL_CTA, $this->invokeTrait('closingInviteLine', $reading, false));
    }

    /** ข้ามเที่ยงคืนไปแล้ว = คนละวัน → ชวนได้ (นับ "วันปฏิทิน" ไม่ใช่ 24 ชม.) */
    public function test_uses_calendar_day_not_rolling_24h(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 22, 0, 5, 0));
        $reading = $this->reading(true, Carbon::create(2026, 8, 21, 23, 55, 0));

        $this->assertFalse($this->invokeTrait('justHadPaidReadingToday', $reading));
    }
}
