<?php

namespace Tests\Unit\Services;

use App\Models\FortuneTellingSetting;
use App\Services\Fortune\GestureFloodGuard;
use PHPUnit\Framework\TestCase;

/**
 * 🎭 ล็อกตัวแยก "ท่าทางล้วน" vs "ข้อความจริง" ของ GestureFloodGuard
 *
 * 🚨 ทำไมต้องมีเทสต์นี้ (2026-09-08):
 *   ตัวแยกนี้คือสิ่งเดียวที่กั้นระหว่าง "แบนคนกวน" กับ "ปิดปากลูกค้าจริง"
 *   พลาดฝั่งหนึ่ง = คนยิงสติกเกอร์ 113 ใบลอยนวล (เคส FTU-260908-Y1018)
 *   พลาดอีกฝั่ง = คนแก่พิมพ์ "5555" หรือ "ดูดวง" แล้วโดนเงียบใส่จนถึงขั้นระงับ 7 วัน
 *
 *   กับดักที่เคยกัดเรามาแล้ว ([[rule_thai_text_matching_traps]]):
 *     • VS16 / ZWJ ในอีโมจิสมัยใหม่ (🕵️‍♂️ = หลาย codepoint) — ตัวจริงที่แสนที ยิงมา
 *     • สระ/วรรณยุกต์ไทยเป็น \p{M} ไม่ใช่ \p{L}
 *     • "5555" (หัวเราะแบบไทย) ไม่มีตัวอักษรเลย มีแต่ตัวเลข
 *
 * ⚠️ ใช้ PHPUnit\Framework\TestCase ตรงๆ (ไม่แตะ DB) — ส่ง settings เปล่าเข้าไป
 *    เครื่อง dev ที่ไม่มี MySQL รันได้ ([[rule_phpunit_needs_mysql_no_sqlite]] ไม่ใช้กับเทสต์นี้)
 */
class GestureFloodContentFreeTest extends TestCase
{
    protected function guard(): GestureFloodGuard
    {
        return new GestureFloodGuard(new FortuneTellingSetting);
    }

    /**
     * ท่าทางล้วน — ต้องถูกจับได้ทุกทรง
     *
     * @return array<string, array{0: string, 1: array}>
     */
    public static function ท่าทางล้วน(): array
    {
        return [
            'อีโมจิเดี่ยว' => ['👍', []],
            'อีโมจิ VS16 + ZWJ ซ้ำ (ของจริงจากแสนที)' => ['🕵️‍♂️🕵️‍♂️🕵️‍♂️', []],
            'อีโมจิผสมช่องว่าง' => ['😍 🙏  👌', []],
            'สติกเกอร์ FB (มาเป็น type=image + sticker_id)' => ['', [['type' => 'image', 'payload' => ['sticker_id' => 369239263222822]]]],
            'สติกเกอร์ type=sticker' => ['', [['type' => 'sticker', 'payload' => []]]],
            'สติกเกอร์ + อีโมจิประกบ' => ['👍', [['type' => 'sticker', 'payload' => []]]],
            'สัญลักษณ์ล้วน' => ['...???!!!', []],
            'อีโมจิสีผิว (skin tone modifier)' => ['👍🏽👍🏽', []],
        ];
    }

    /**
     * @dataProvider ท่าทางล้วน
     */
    public function test_จับท่าทางล้วนได้(string $text, array $attachments): void
    {
        $this->assertTrue(
            $this->guard()->isContentFree($text, $attachments),
            "ควรถูกนับเป็นท่าทางล้วน: {$text}"
        );
    }

    /**
     * ข้อความจริง — ห้ามถูกนับเป็นท่าทางเด็ดขาด
     *
     * @return array<string, array{0: string, 1: array}>
     */
    public static function ข้อความจริง(): array
    {
        return [
            'คำไทยสั้น' => ['ดูดวง', []],
            'คำไทยพยางค์เดียวมีสระบน' => ['มี', []],
            'หัวเราะแบบไทย (ตัวเลขล้วน)' => ['5555', []],
            'ยอดเงิน' => ['39.77', []],
            'ข้อความปนอีโมจิ' => ['สนใจดูดวงค่ะ 🙏', []],
            'ปุ่มที่หล่นมาเป็นข้อความ' => ['🔮 ดูดวงเลย', []],
            'ภาษาอังกฤษ' => ['ok', []],
            'รูปภาพ (ไม่ใช่งานของด่านนี้)' => ['', [['type' => 'image', 'payload' => ['url' => 'https://x/y.jpg']]]],
            'สลิปโอนเงิน + ข้อความ' => ['โอนแล้วค่ะ', [['type' => 'image', 'payload' => ['url' => 'https://x/slip.jpg']]]],
            'ลิงก์แชร์ (type=fallback)' => ['', [['type' => 'fallback', 'payload' => ['url' => 'https://fb.com/reel/1']]]],
            'สติกเกอร์มาพร้อมรูปจริง' => ['', [['type' => 'sticker'], ['type' => 'image', 'payload' => ['url' => 'https://x/y.jpg']]]],
            'ว่างเปล่าไม่มีอะไรเลย (webhook ping)' => ['', []],
        ];
    }

    /**
     * @dataProvider ข้อความจริง
     */
    public function test_ไม่จับข้อความจริงผิด(string $text, array $attachments): void
    {
        $this->assertFalse(
            $this->guard()->isContentFree($text, $attachments),
            "ห้ามถูกนับเป็นท่าทางล้วน: {$text}"
        );
    }
}
