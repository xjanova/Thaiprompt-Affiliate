<?php

namespace Tests\Unit\Services;

use App\Services\FortuneConversationService;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 💸 (2026-09-13) ตัวจับ "ลูกค้าบอกว่าโอนแล้ว" ต้องรู้จักสำนวนที่คนไทยพูดกันจริง
 *
 * เคสจริง Pantaree Donpar (FB 26273302092329161, FTU-260913-S0328):
 *   12:35 ส่งรูปสลิป (ไม่มีบิลค้าง → เก็บเงียบ รอคำว่าโอนแล้ว)
 *   12:35 พิมพ์ "หนูโอนให้แล้วนะคะ" → ตัวจับไม่ติด (รับได้แค่ "โอนแล้ว"/"โอนไปแล้ว")
 *   → สลิปที่เก็บไว้ไม่ถูกดึงไปตรวจ + ข้อความไหลไป AI แชท ตอบให้ไปพิมพ์ "ดูดวง" เปิด Celtic
 *
 * สองระดับ:
 *   • เข้ม (เดิม) — ใช้ในทางที่ลูกค้าไม่มีบิล (looksLikePaidNotReceived)
 *   • หลวม (ใหม่) — "โอนให้แล้ว" / "โอนเข้าไปแล้ว" ใช้เมื่อมีบิลรอจ่าย หรือมีร่องรอยการจ่าย
 *     (ทางไม่มีบิลเช็คประวัติก่อน — ดู FortuneAbandonedDeepBillSlipTest)
 *
 * ขาที่ห้ามหลุด: เรื่องเล่า "เงินเข้าหาผู้พูด" (แฟนโอนมา / เงินเดือนโอนเข้า) ต้องไม่เป็นเคลมทั้งสองระดับ
 * — รีวิวก่อนพุชเจอ 10 ประโยคนี้ถูกทวงสลิปผิด ๆ ในร่างแรก
 *
 * ไม่ใช้ DB — เมธอดแตะแค่ regex + normalizeUserInput
 */
class FortunePaidClaimPhrasingTest extends TestCase
{
    protected FortuneConversationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = (new ReflectionClass(FortuneConversationService::class))
            ->newInstanceWithoutConstructor();
    }

    private function isClaim(string $text, bool $allowLoose): bool
    {
        $m = new ReflectionMethod($this->service, 'isPaymentClaimRequest');
        $m->setAccessible(true);

        return (bool) $m->invoke($this->service, $text, $allowLoose);
    }

    /**
     * เดิมติดอยู่แล้ว — ต้องยังติดทั้งสองระดับ
     *
     * @return array<string, array{0: string}>
     */
    public static function strictClaimPhrases(): array
    {
        return [
            'โอนแล้ว' => ['โอนแล้วค่ะ'],
            'โอนไปแล้ว' => ['โอนไปแล้วค่ะ'],
            'โอนค่าครูแล้ว' => ['โอนค่าครูแล้วค่ะ'],
            'โอนเงินค่าครูเรียบร้อย' => ['โอนเงินค่าครูเรียบร้อย'],
            'โอนเรียบร้อย' => ['โอนเรียบร้อยค่ะ'],
        ];
    }

    /**
     * สำนวนหลวม — ติดเมื่อมีบริบทการจ่าย แต่ทางไม่มีบิล (เข้ม) ต้องยังไม่ติด
     *
     * @return array<string, array{0: string}>
     */
    public static function looseClaimPhrases(): array
    {
        return [
            'ข้อความจริงของลูกค้า' => ['หนูโอนให้แล้วนะคะ'],
            'โอนให้แล้ว' => ['โอนให้แล้วค่ะ'],
            'โอนเงินให้แล้ว' => ['โอนเงินให้แล้วค่ะ'],
            'โอนให้แม่หมอแล้ว' => ['หนูโอนให้แม่หมอแล้วค่ะ'],
            'จ่ายให้แล้ว' => ['จ่ายให้แล้วนะคะ'],
            'โอนให้เรียบร้อยแล้ว' => ['โอนให้เรียบร้อยแล้วค่ะ'],
            'โอนเข้าไปแล้ว' => ['โอนเข้าไปแล้วค่ะ'],
            'โอนไปให้แล้ว' => ['โอนไปให้แล้วนะคะ'],
            'โอนให้ทางเพจแล้ว' => ['โอนให้ทางเพจแล้วค่ะ'],
            // คำอนาคตที่อยู่ "หลัง" คำกริยา = โอนแล้วจริง แค่นัดดูทีหลัง
            'โอนแล้ว นัดดูพรุ่งนี้' => ['โอนให้แล้ว พรุ่งนี้ค่อยมาดูนะคะ'],
            'บอกเวลาในอดีต' => ['เมื่อเช้าโอนให้แล้วค่ะ'],
            // คำอนาคตอยู่ไกลจากคำกริยา = พูดเรื่องอื่นก่อน ไม่ใช่นัดโอน
            'พรุ่งนี้อยู่ไกล' => ['พรุ่งนี้หนูมีสอบ แต่หนูโอนให้แล้วนะคะ'],
            // มีคนอื่นจ่ายแทน — ในบริบทบิลรอจ่ายนับเป็นเคลม (ทางไม่มีบิลต้องมีร่องรอยก่อน)
            'พ่อจ่ายให้' => ['พ่อจ่ายให้แล้วค่ะ'],
        ];
    }

    /**
     * ต้องไม่ติดทั้งสองระดับ
     *
     * @return array<string, array{0: string}>
     */
    public static function notClaimPhrases(): array
    {
        return [
            // จริงจาก log prod (text_preview ถูกตัดที่ 30 ตัวอักษร)
            'จริง 2026-09-07: ขอเป็นพรุ่งนี้' => ['คะขอเป็นพรุ่งนี้โอนให้แล้วดูพร'],
            'จริง 2026-09-13: ถ้าโอนให้ 99' => ['ถ้าหนูโอนให้ 99 แล้วให้หนูจะเป'],
            'พรุ่งนี้นำหน้า' => ['พรุ่งนี้โอนให้แล้วจะทักมานะคะ'],
            'ทีหลังนำหน้า' => ['ทีหลังค่อยโอนมาแล้วกัน'],
            'จะโอน' => ['จะโอนให้แล้วนะคะ'],
            'ยังไม่ได้โอน' => ['ยังไม่ได้โอนให้เลยค่ะ'],
            'ไม่มีเงินโอน' => ['ไม่มีเงินโอนให้แล้วค่ะ'],
            'เป็นคำถาม' => ['โอนให้แล้วได้ดูเลยไหมคะ'],
            'เดี๋ยวโอน' => ['เดี๋ยวโอนให้นะคะ'],
            'เดิม: ถามก่อนจ่าย' => ['จ่ายค่าครูแล้วได้ดูเลยไหม'],
            'เดิม: ขอโอนแล้วกัน' => ['ขอโอนพรุ่งนี้แล้วกันนะคะ'],
            'ไม่เกี่ยวกับเงิน' => ['ดูดวง 39'],
            // เงินเข้าหาผู้พูด (มา / เข้า) — เรื่องเล่า ไม่ใช่จ่ายค่าครู
            'แฟนโอนมา' => ['แฟนโอนมาแล้ว'],
            'เขาโอนเงินมา' => ['เขาโอนเงินมาแล้วค่ะ'],
            'แม่โอนมาให้' => ['แม่โอนมาให้แล้ว'],
            'ลูกหนี้โอนมา' => ['ลูกหนี้โอนมาแล้วค่ะ'],
            'เงินเดือนโอนเข้า' => ['เงินเดือนโอนเข้าแล้ว'],
            'บริษัทโอนเข้า' => ['บริษัทโอนเข้าแล้วค่ะ'],
            'เขาจ่ายมา' => ['เขาจ่ายมาแล้วครึ่งนึง'],
            'จ่ายมาเยอะ' => ['ดูดวงมาหลายที่ จ่ายมาแล้วเยอะมาก'],
            'โอนมาแล้ว' => ['โอนมาแล้วค่ะ'],
        ];
    }

    /**
     * @dataProvider strictClaimPhrases
     */
    public function test_existing_claims_still_recognised_everywhere(string $text): void
    {
        $this->assertTrue($this->isClaim($text, false), "เข้ม: ต้องนับเป็นการแจ้งโอน: {$text}");
        $this->assertTrue($this->isClaim($text, true), "หลวม: ต้องนับเป็นการแจ้งโอน: {$text}");
        $this->assertTrue($this->service->looksLikePaidNotReceived($text), "ทางไม่มีบิล: {$text}");
    }

    /**
     * @dataProvider looseClaimPhrases
     */
    public function test_loose_claims_need_payment_context(string $text): void
    {
        $this->assertTrue($this->isClaim($text, true), "มีบิลรอจ่าย: ต้องนับเป็นการแจ้งโอน: {$text}");
        $this->assertFalse(
            $this->service->looksLikePaidNotReceived($text),
            "ทางไม่มีบิล: ห้ามนับจากการสะกดอย่างเดียว (ต้องผ่าน hasRecentPaymentContext): {$text}"
        );
    }

    /**
     * @dataProvider notClaimPhrases
     */
    public function test_future_negative_or_money_received_phrases_are_not_claims(string $text): void
    {
        $this->assertFalse($this->isClaim($text, true), "หลวม: ต้องไม่นับเป็นการแจ้งโอน: {$text}");
        $this->assertFalse($this->isClaim($text, false), "เข้ม: ต้องไม่นับเป็นการแจ้งโอน: {$text}");
    }
}
