<?php

namespace Tests\Unit\Services;

use App\Services\FortuneConversationService;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🌙 (2026-08-02, owner: "ไม่ส่งคำทำนายฟรี") ลูกค้าพิมพ์ชื่อวันเกิดมาเอง = ต้องได้ดวงฟรี
 *
 * เคสจริงบน prod (user 26806555292314388, 20:18): ลูกค้าใหม่เอี่ยมทักมาครั้งแรกว่า
 * "@Meta AI 🟢 พุธ" (ก๊อปจากคอมเมนต์) — เราไม่เคยทักไปถาม จึงไม่มีธง daily_pending
 * → ด่านที่ 2 ของ DailyHoroscopeModeTrait ตีตก → ตกไป AI chat ทั่วไป
 * → ลูกค้าขอดวงฟรีแล้วไม่ได้ของ (log: ProcessBufferedChatMessageJob → tryAIChatResponse)
 *
 * 🇹🇭 กับดักที่เจอตอนเขียน: `[^\p{L}\p{N}\s]` กินสระ/วรรณยุกต์ไทยทิ้ง เพราะมันเป็น
 *    Mark (\p{M}) ไม่ใช่ Letter → "พุธ" กลายเป็น "พ ธ" แล้วเทียบไม่ติดสักคำ
 *
 * ไม่ต้องใช้ DB — เมธอดที่เทสต์เป็น pure string function (เรียกผ่าน Reflection)
 */
class FortuneDailyColdDayNameTest extends TestCase
{
    protected FortuneConversationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = (new ReflectionClass(FortuneConversationService::class))
            ->newInstanceWithoutConstructor();
    }

    protected function invokeHidden(string $method, ...$args)
    {
        $m = new ReflectionMethod($this->service, $method);
        $m->setAccessible(true);

        return $m->invoke($this->service, ...$args);
    }

    /**
     * ชื่อวันเดี่ยว ๆ ที่ลูกค้าพิมพ์มาเอง — ต้องรับทุกแบบที่พบจริง
     *
     * @test
     */
    public function ชื่อวันเดี่ยวๆต้องรับเป็นคำขอดวงฟรี(): void
    {
        $this->assertTrue($this->invokeHidden('looksLikeStandaloneDayName', '🟢 พุธ'), 'เคสจริงที่ owner รายงาน');
        $this->assertTrue($this->invokeHidden('looksLikeStandaloneDayName', '@Meta AI 🟢 พุธ'), 'เผื่อ mention หลุดด่าน strip');
        $this->assertTrue($this->invokeHidden('looksLikeStandaloneDayName', 'พุธ'));
        $this->assertTrue($this->invokeHidden('looksLikeStandaloneDayName', 'วันพุธ'));
        $this->assertTrue($this->invokeHidden('looksLikeStandaloneDayName', 'พุธค่ะ'));
        $this->assertTrue($this->invokeHidden('looksLikeStandaloneDayName', 'เกิดวันพุธค่ะ'));
        $this->assertTrue($this->invokeHidden('looksLikeStandaloneDayName', 'พฤหัสบดี'));
        $this->assertTrue($this->invokeHidden('looksLikeStandaloneDayName', 'เสาร์ครับ'));
        $this->assertTrue($this->invokeHidden('looksLikeStandaloneDayName', '  อาทิตย์  '));
        $this->assertTrue($this->invokeHidden('looksLikeStandaloneDayName', 'จันทร์นะคะ'));
    }

    /**
     * ประโยคที่มีชื่อวันปนอยู่ = คำถามจริง ห้ามกลืนไปเป็นดวงรายวัน
     *
     * @test
     */
    public function ประโยคที่มีชื่อวันปนต้องไม่ถูกกลืน(): void
    {
        $this->assertFalse($this->invokeHidden('looksLikeStandaloneDayName', 'วันพุธนี้จะไปหาหมอค่ะ'));
        $this->assertFalse($this->invokeHidden('looksLikeStandaloneDayName', 'ศุกร์นี้เงินเดือนออกไหม'));
        $this->assertFalse($this->invokeHidden('looksLikeStandaloneDayName', 'เกิดวันพุธ ปี 2530 ค่ะ อยากดูดวงความรัก'));
        $this->assertFalse($this->invokeHidden('looksLikeStandaloneDayName', 'พุธ อังคาร'), 'สองวัน = กำกวม');
        $this->assertFalse($this->invokeHidden('looksLikeStandaloneDayName', 'สวัสดีค่ะ'));
        $this->assertFalse($this->invokeHidden('looksLikeStandaloneDayName', '39'), 'ตัวเลขแพ็กเกจ ห้ามหลุด');
        $this->assertFalse($this->invokeHidden('looksLikeStandaloneDayName', ''));
    }

    /**
     * ตัวจับชื่อวันในประโยค (ใช้ตอนตอบคำถามที่เราถามไปแล้ว) ต้องไม่เปลี่ยนพฤติกรรม
     *
     * @test
     */
    public function detect_thai_day_nameต้องทำงานเหมือนเดิม(): void
    {
        $this->assertSame(3, $this->invokeHidden('detectThaiDayName', 'พุธ'));
        $this->assertSame(4, $this->invokeHidden('detectThaiDayName', 'วันพฤหัสบดี'), 'คำยาวต้องมาก่อน พฤหัส');
        $this->assertSame(6, $this->invokeHidden('detectThaiDayName', 'เกิดวันเสาร์ค่ะ'));
        $this->assertSame(0, $this->invokeHidden('detectThaiDayName', 'อาทิตย์'));
        $this->assertNull($this->invokeHidden('detectThaiDayName', 'สวัสดี'));
    }
}
