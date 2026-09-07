<?php

namespace Tests\Unit\Services;

use App\Services\FortuneConversationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * 🤫 ทดสอบตัวจับ "ข้อความไม่มีเนื้อความ" (looksLikeContentFreeFiller)
 *
 * owner spec (2026-09-07): *"ถ้าผู้ใช้พิมพ์ ค่ะ ค่ะ ไปเรื่อยๆ ให้ถามว่าหยุดใช่ไหม
 *   และจบการสนทนา ฉลาดพอจะรู้ว่าไม่มีสัญญาณในการคุยแล้ว"*
 *
 * เคสจริงที่จุดชนวน — FTU-260907-C5731 (LINE · Celtic 99฿ · ช่วงคุยต่อหลังบทสรุป):
 *   ลูกค้าพิมพ์ "คะ" เปล่า ๆ 5 ครั้งติดระหว่าง 10:44–10:47 น. บอทยิง AI ตอบครบทั้ง 5 ครั้ง
 *   (`action=pro_session_answer` ทุกรอบ) ทั้งที่ไม่มีคำถามสักคำ = จ่ายค่า token ให้ความเงียบ
 *   ตัวกรองสแปมเห็นแล้วด้วย (`reason=repetitive`) แต่ bypass เพราะลูกค้าจ่ายเงินแล้ว
 *
 * 🚨 ทำไมต้องมีเทสต์นี้:
 *   ตัวจับนี้ตัดสิน "จะปิดการสนทนาของคนที่จ่ายเงินแล้วไหม" — false positive 1 ครั้ง
 *   = ตัดบทลูกค้าที่กำลังจะถามต่อ. คำไทยพยางค์เดียวใน whitelist ("ได้"/"เค"/"ออ")
 *   ฝังอยู่ในคำอื่นเต็มไปหมด ⇒ ถ้าใครเผลอเปลี่ยนไปเทียบแบบ substring เทสต์นี้ต้องแดงทันที
 *   (กับดักเดียวกับ "เหมาะ" ที่เคยติดกับ "หมา")
 *
 * ⚠️ ไม่ทดสอบตัวด่าน (handleProSessionIdleFillerGate) ที่นี่ — มันเขียน conversation_state
 *   ผ่าน `FortuneReading::update()` ซึ่งเงียบเมื่อโมเดลยังไม่ถูก save ⇒ ต้องมี MySQL จริง
 *   ตรงนี้ล็อกเฉพาะ "ตัวตัดสิน" ที่เป็น pure function
 *
 * ⚠️ ใช้ PHPUnit\Framework\TestCase ตรง ๆ (ไม่แตะ DB) เครื่อง dev ที่ไม่มี MySQL รันได้
 */
class FortuneIdleFillerTest extends TestCase
{
    protected FortuneConversationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = (new ReflectionClass(FortuneConversationService::class))
            ->newInstanceWithoutConstructor();
    }

    protected function isFiller(string $text): bool
    {
        $ref = new ReflectionMethod(FortuneConversationService::class, 'looksLikeContentFreeFiller');
        $ref->setAccessible(true);

        return (bool) $ref->invoke($this->service, $text);
    }

    /**
     * @return array<string, array{0:string}>
     */
    public static function fillerProvider(): array
    {
        $cases = [
            // คำลงท้ายสุภาพล้วน — แกนของสเปกเจ้าของ
            'ค่ะ', 'คะ', 'ค่า', 'ครับ', 'คับ', 'จ้า', 'จ้ะ',
            // พิมพ์ซ้อน — normalize ตัดท้ายแค่ 2 รอบ ต้องวนจนนิ่ง
            'ค่ะค่ะ', 'คะคะคะ',
            // มีช่องว่าง / จุด ห้อยท้าย
            'ค่ะ ', 'ค่ะ.', '...',
            // อีโมจิล้วน (VS16 ต้องไม่ค้าง)
            '👍', '🙏', '❤️',
            // เสียงรับรู้สั้น ๆ ที่ normalize ตัดไม่ได้
            'ok', 'OK', 'โอเค', 'โอเคค่ะ', 'อืม', 'อือ', 'อ๋อ',
            'ได้ค่ะ', 'รับทราบค่ะ', 'เข้าใจแล้วค่ะ', '555',
        ];

        return array_combine(
            array_map(fn ($t) => 'ฟิลเลอร์: '.$t, $cases),
            array_map(fn ($t) => [$t], $cases)
        );
    }

    /**
     * @return array<string, array{0:string}>
     */
    public static function realMessageProvider(): array
    {
        $cases = [
            // คำถามจริง — ห้ามตัดบทเด็ดขาด
            'แล้วเรื่องงานล่ะคะ',
            'เขาจะกลับมาไหมคะ',
            'อยากรู้เรื่องการเงินค่ะ',
            'ดูเรื่องเงินให้หน่อยค่ะ',
            'ขอบคุณค่ะ แล้วเรื่องงานล่ะ',
            'โอเคค่ะ แล้วเรื่องลูกล่ะ',

            // 🪤 กับดัก substring — พยางค์ใน whitelist ฝังอยู่กลางคำจริง
            'ได้ยินว่าเขามีคนใหม่',   // "ได้"
            'เคยรักกันมากค่ะ',        // "เค"
            'ออกจากงานดีไหมคะ',      // "ออ"
            'เหมาะกับงานไหนคะ',       // "เหมาะ"/"หมา"

            // ข้อความที่ด่านอื่นต้องได้จัดการก่อน — ตัวนี้ต้องไม่ไปแย่ง
            'ขอบคุณค่ะ',   // คำลา → looksLikeCelticFarewell
            'พอแล้วค่ะ',   // สั่งจบตรง ๆ → matchesExactKeyword
            'ใช่ค่ะ',      // ยืนยันปิด → isProSessionExitConfirmed
            'ไม่ค่ะ',
            'ยังค่ะ',
            'ดูดวง',       // ขอเปิดรอบใหม่
            '39',
            '99',
            'เกิดวันจันทร์ค่ะ',
        ];

        return array_combine(
            array_map(fn ($t) => 'ข้อความจริง: '.$t, $cases),
            array_map(fn ($t) => [$t], $cases)
        );
    }

    #[DataProvider('fillerProvider')]
    public function test_ข้อความไม่มีเนื้อความต้องถูกจับเป็นฟิลเลอร์(string $text): void
    {
        $this->assertTrue(
            $this->isFiller($text),
            "\"{$text}\" ควรถูกนับเป็นฟิลเลอร์ (ไม่มีสัญญาณคุยต่อ) แต่หลุดไปให้ AI ตอบ"
        );
    }

    #[DataProvider('realMessageProvider')]
    public function test_ข้อความที่มีเนื้อความต้องไม่ถูกจับเป็นฟิลเลอร์(string $text): void
    {
        $this->assertFalse(
            $this->isFiller($text),
            "\"{$text}\" มีเนื้อความ/เจตนาชัดเจน — ถ้าถูกนับเป็นฟิลเลอร์ ลูกค้าที่จ่ายเงินแล้วจะโดนตัดบท"
        );
    }

    public function test_ข้อความยาวไม่ถูกนับเป็นฟิลเลอร์แม้ลงท้ายด้วยคำสุภาพ(): void
    {
        $this->assertFalse(
            $this->isFiller('แม่หมอคะ หนูอยากรู้ว่าเขาจะกลับมาเมื่อไหร่ค่ะ'),
            'ประโยคยาวต้องไหลไปให้ AI ตอบเสมอ'
        );
    }

    public function test_ข้อความว่างไม่ถูกนับเป็นฟิลเลอร์(): void
    {
        // ข้อความว่างไม่ควรไปเด้งตัวนับ — ปล่อยให้ด่านอื่นจัดการตามเดิม
        $this->assertFalse($this->isFiller(''));
        $this->assertFalse($this->isFiller('   '));
    }
}
