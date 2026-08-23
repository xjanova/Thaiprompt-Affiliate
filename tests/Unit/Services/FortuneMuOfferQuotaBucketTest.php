<?php

namespace Tests\Unit\Services;

use App\Models\FortuneProductOffer;
use Tests\TestCase;

/**
 * ล็อกสัญญาของ "โควตา 2 กระเป๋า" ในการเสนอสินค้าท้ายบิล
 *
 * เคสที่ทำให้ต้องมีเทสต์นี้ (2026-08-23, Zurich Mock):
 *   ลูกค้ารับดวงฟรีตอน 13:20 → ได้การ์ดสินค้า 1 รอบ → โควตารายวัน (cap=1) หมด
 *   บ่ายจ่าย 99 ดูจบ 15:35 → `celtic_end` โดนเพดานเดียวกันตีตก = ไม่ได้การ์ดเลย
 *
 * ตัวที่พังได้เงียบที่สุดคือ "ลิสต์สองอันหลุดจากกัน" — เพิ่มจุดยิงใหม่ที่ลิสต์เดียว
 * แล้วอีกกระเป๋าจะนับไม่ครบ/นับซ้ำ โดยไม่มี error ให้เห็น เทสต์นี้จับตรงนั้น
 *
 * DB-free โดยตั้งใจ — เทียบเฉพาะค่าคงที่ + ฟังก์ชันบริสุทธิ์ ไม่แตะฐานข้อมูล
 */
class FortuneMuOfferQuotaBucketTest extends TestCase
{
    /** @test */
    public function จุดยิงท้ายบิลจ่ายเงินต้องเป็นสับเซ็ตของจุดที่บอทเสนอเอง(): void
    {
        foreach (FortuneProductOffer::PAID_END_TRIGGERS as $trigger) {
            $this->assertContains(
                $trigger,
                FortuneProductOffer::PROACTIVE_TRIGGERS,
                "จุดยิง [{$trigger}] อยู่ใน PAID_END_TRIGGERS แต่หายไปจาก PROACTIVE_TRIGGERS ".
                '⇒ ด่านเพดานรายวันจะมองไม่เห็น = ยิงได้ไม่จำกัด'
            );
        }
    }

    /** @test */
    public function สองกระเป๋าต้องไม่ทับกันและรวมกันได้ครบ(): void
    {
        $unpaid = FortuneProductOffer::unpaidProactiveTriggers();

        $this->assertSame(
            [],
            array_values(array_intersect($unpaid, FortuneProductOffer::PAID_END_TRIGGERS)),
            'กระเป๋าปกติกับกระเป๋าท้ายบิลทับกัน = รอบเดียวถูกนับสองกระเป๋า'
        );

        $this->assertEqualsCanonicalizing(
            FortuneProductOffer::PROACTIVE_TRIGGERS,
            array_merge($unpaid, FortuneProductOffer::PAID_END_TRIGGERS),
            'รวมสองกระเป๋าแล้วต้องได้จุดยิงครบเท่าเดิม ไม่มีจุดไหนหลุดออกจากการนับ'
        );
    }

    /** @test */
    public function ท้ายบิลเซลติกและเจาะลึกต้องอยู่ในกระเป๋าท้ายบิล(): void
    {
        $this->assertContains(FortuneProductOffer::TRIGGER_CELTIC_END, FortuneProductOffer::PAID_END_TRIGGERS);
        $this->assertContains(FortuneProductOffer::TRIGGER_DEEP_END, FortuneProductOffer::PAID_END_TRIGGERS);
    }

    /** @test */
    public function ดวงฟรีรายวันต้องไม่กินโควตาของท้ายบิล(): void
    {
        $this->assertNotContains(
            FortuneProductOffer::TRIGGER_DAILY_FREE,
            FortuneProductOffer::PAID_END_TRIGGERS,
            'ของฟรีตอนเช้าห้ามอยู่กระเป๋าเดียวกับคนจ่ายเงิน — นี่คือบั๊กเดิมของเคส Zurich Mock'
        );

        $this->assertContains(
            FortuneProductOffer::TRIGGER_DAILY_FREE,
            FortuneProductOffer::unpaidProactiveTriggers()
        );
    }

    /** @test */
    public function ลูกค้าถามหาของเองต้องไม่ถูกนับในกระเป๋าไหนเลย(): void
    {
        $this->assertNotContains(FortuneProductOffer::TRIGGER_CUSTOMER_ASK, FortuneProductOffer::PROACTIVE_TRIGGERS);
        $this->assertNotContains(FortuneProductOffer::TRIGGER_CUSTOMER_ASK, FortuneProductOffer::PAID_END_TRIGGERS);
        $this->assertNotContains(FortuneProductOffer::TRIGGER_CUSTOMER_ASK, FortuneProductOffer::unpaidProactiveTriggers());
    }
}
