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
 *   → แอดมินต้องสั่งเปิดบิลใหม่ แล้วกดอนุมัติเอง
 *
 * เทสต์นี้ตรึง 2 ขา (ห้ามหลุดทั้งคู่):
 *   1. สำนวนเคลมที่มีคำคั่นกลาง (ให้/มา/เข้า/ให้แม่หมอ) ต้องติด
 *   2. ประโยคอนาคต/สมมติ/ปฏิเสธต้อง **ไม่** ติด — รวม 2 ประโยคจริงจาก log prod
 *      ที่ถ้าขยาย regex แบบไม่ระวังจะถูกนับเป็นเคลมผิด ๆ
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

    private function isClaim(string $text): bool
    {
        $m = new ReflectionMethod($this->service, 'isPaymentClaimRequest');
        $m->setAccessible(true);

        return (bool) $m->invoke($this->service, $text);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function claimPhrases(): array
    {
        return [
            'ข้อความจริงของลูกค้า' => ['หนูโอนให้แล้วนะคะ'],
            'โอนให้แล้ว' => ['โอนให้แล้วค่ะ'],
            'โอนเงินให้แล้ว' => ['โอนเงินให้แล้วค่ะ'],
            'โอนให้แม่หมอแล้ว' => ['หนูโอนให้แม่หมอแล้วค่ะ'],
            'โอนมาแล้ว' => ['โอนมาแล้วค่ะ'],
            'จ่ายให้แล้ว' => ['จ่ายให้แล้วนะคะ'],
            'โอนให้เรียบร้อยแล้ว' => ['โอนให้เรียบร้อยแล้วค่ะ'],
            'โอนเข้าไปแล้ว' => ['โอนเข้าไปแล้วค่ะ'],
            'โอนไปให้แล้ว' => ['โอนไปให้แล้วนะคะ'],
            'โอนให้ทางเพจแล้ว' => ['โอนให้ทางเพจแล้วค่ะ'],
            // คำอนาคตที่อยู่ "หลัง" คำกริยา = โอนแล้วจริง แค่นัดดูทีหลัง
            'โอนแล้ว นัดดูพรุ่งนี้' => ['โอนให้แล้ว พรุ่งนี้ค่อยมาดูนะคะ'],
            'บอกเวลาในอดีต' => ['เมื่อเช้าโอนให้แล้วค่ะ'],
            // ตัวควบคุม — เดิมติดอยู่แล้ว ต้องยังติด
            'เดิม: โอนแล้ว' => ['โอนแล้วค่ะ'],
            'เดิม: โอนไปแล้ว' => ['โอนไปแล้วค่ะ'],
            'เดิม: โอนค่าครูแล้ว' => ['โอนค่าครูแล้วค่ะ'],
            'เดิม: โอนเงินค่าครูเรียบร้อย' => ['โอนเงินค่าครูเรียบร้อย'],
            'เดิม: โอนเรียบร้อย' => ['โอนเรียบร้อยค่ะ'],
        ];
    }

    /**
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
        ];
    }

    /**
     * @dataProvider claimPhrases
     */
    public function test_transfer_claims_are_recognised(string $text): void
    {
        $this->assertTrue($this->isClaim($text), "ต้องนับเป็นการแจ้งโอน: {$text}");
        $this->assertTrue(
            $this->service->looksLikePaidNotReceived($text),
            "cold path (ไม่มีบิลค้าง) ต้องรับเคลมนี้ด้วย: {$text}"
        );
    }

    /**
     * @dataProvider notClaimPhrases
     */
    public function test_future_hypothetical_or_negative_phrases_are_not_claims(string $text): void
    {
        $this->assertFalse($this->isClaim($text), "ต้องไม่นับเป็นการแจ้งโอน: {$text}");
    }
}
