<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\FortuneEntryCardBuilder;
use App\Services\FortuneConversationService;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🃏 (2026-08-28, owner) "พาไป 2 การ์ด ก่อนดีกว่า ที่มีให้ดูฟรี กับ ดู vip"
 *
 * เดิม: คน **กดปุ่ม** ได้การ์ดมีรูป · คน **พิมพ์** ว่าอยากดูดวงรายวัน/ขอดูฟรี ได้ข้อความล้วน
 * ⇒ ฟีเจอร์เดียวกัน หน้าตาคนละแบบ แล้วแต่ประตูที่เดินเข้ามา
 *
 * เทสต์นี้ล็อก "สัญญา" 2 ข้อที่เส้นใหม่พึ่งพา — ทั้งคู่พังแบบ **เงียบ** ถ้าใครแก้:
 *   1. ตัวจับเจตนา (looksLikeDailyIntent) ต้องรับประโยคที่ลูกค้าพิมพ์จริง
 *      พังแล้ว = ตกไป AI chat / เมนูราคา ไม่มี error ให้เห็น
 *   2. payload ของปุ่มบนการ์ดต้องเป็น DAILY_FREE_START / DAILY_VIP_PACKAGES
 *      พังแล้ว = การ์ดยังส่งออกสวยงาม แต่กดปุ่มไม่มีอะไรเกิดขึ้น
 *
 * ไม่ต้องใช้ DB — useOverrides(collect()) ตัดขา FortuneEntryCard::overrides()
 * (rule_phpunit_needs_mysql_no_sqlite: เทสต์ที่แตะ DB รันบนเครื่อง dev ไม่ได้)
 */
class FortuneEntryCardChatLaneTest extends TestCase
{
    protected FortuneConversationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = (new ReflectionClass(FortuneConversationService::class))
            ->newInstanceWithoutConstructor();
    }

    /** เรียกเมธอด protected ผ่าน Reflection */
    protected function invokeHidden(string $method, ...$args)
    {
        $m = new ReflectionMethod($this->service, $method);
        $m->setAccessible(true);

        return $m->invoke($this->service, ...$args);
    }

    /** ตัวสร้างการ์ดที่ไม่แตะ DB */
    protected function builder(): FortuneEntryCardBuilder
    {
        return (new FortuneEntryCardBuilder)->useOverrides(collect());
    }

    /**
     * 1️⃣ ประโยคที่ลูกค้าพิมพ์จริง ต้องเข้าเลนรายวันได้
     *
     * ทุกประโยคในลิสต์นี้แปลว่า "ขอดวงรายวัน" — ถ้าตัวใดตัวหนึ่งคืน false
     * คนกลุ่มนั้นจะไม่มีวันเห็นการ์ด 2 ใบเลย ไม่ว่าฝั่งส่งจะถูกต้องแค่ไหน
     */
    public function test_ประโยคขอดวงรายวันเข้าเลนรายวันได้(): void
    {
        foreach ([
            'ดูดวงรายวัน',
            'ดูดวงรายวันค่ะ',
            'อยากดูดวงรายวัน',
            'ขอดวงประจำวันหน่อยค่ะ',
            'ดวงรายวันวันนี้',
            'ขอดูดวงวันนี้หน่อย',
        ] as $text) {
            $this->assertTrue(
                (bool) $this->invokeHidden('looksLikeDailyIntent', $text),
                "ควรจับเจตนา 'ขอดวงรายวัน' ได้: {$text}"
            );
        }
    }

    /**
     * 2️⃣ ประโยคที่ **ห้าม** ถูกลากเข้าเลนฟรี — คนกำลังจะจ่ายเงิน
     *
     * ด่านนี้สำคัญกว่าข้อ 1: ดึงคนอยากซื้อเข้าโหมดฟรี = เสียยอดจริง
     */
    public function test_ประโยคสายจ่ายเงินต้องไม่ถูกลากเข้าเลนฟรี(): void
    {
        foreach ([
            'ดูดวงรายวันกับ 99 อันไหนดี',
            'ดูดวงละเอียดราคาเท่าไหร่',
            'ไม่อยากดูดวงแล้ว',
            'ขอคุยกับแอดมิน',
            'โอนเงินแล้วค่ะ',
        ] as $text) {
            $this->assertFalse(
                (bool) $this->invokeHidden('looksLikeDailyIntent', $text),
                "ห้ามลากเข้าเลนดวงฟรี: {$text}"
            );
        }
    }

    /**
     * 3️⃣ การ์ด 2 ใบต้องประกอบได้ + ปุ่มพาไปที่เดิมกับคนกดปุ่ม
     *
     * DAILY_FREE_START → handleDailyShowMine() → รู้วันเกิดได้ดวงเลย / ไม่รู้ก็ต่อการ์ด 7 ใบ
     * เปลี่ยน payload นี้เมื่อไหร่ = การ์ดยังสวยแต่กดแล้วเงียบ
     */
    public function test_การ์ดสองใบมีปุ่มพาไปเส้นเดิม(): void
    {
        $payload = $this->builder()->facebookEntry([
            'invite_text' => "🌙 วันนี้แม่หมอมีดวงประจำวันเกิดแจกฟรีค่ะ\nกดปุ่มด้านล่างได้เลย ไม่มีค่าใช้จ่าย ✨",
            'deep_price' => 39,
        ]);

        $this->assertNotNull($payload, 'ประกอบการ์ด 2 ใบไม่สำเร็จ (รูปหาย?) — เส้นแชทจะตกกลับข้อความเดิมทั้งหมด');

        $elements = $payload['attachment']['payload']['elements'] ?? [];

        $this->assertCount(2, $elements, 'ต้องมี 2 ใบเสมอ — ใบเดียวคือลูกค้าไม่เห็นอีกทางเลือก');
        $this->assertSame('generic', $payload['attachment']['payload']['template_type'] ?? null);

        $this->assertSame('DAILY_FREE_START', $elements[0]['buttons'][0]['payload'] ?? null);
        $this->assertSame('DAILY_VIP_PACKAGES', $elements[1]['buttons'][0]['payload'] ?? null);

        foreach ($elements as $i => $element) {
            $this->assertNotEmpty($element['image_url'] ?? '', "การ์ดใบที่ {$i} ไม่มีรูป");
            $this->assertStringStartsWith('https://', $element['image_url'], "รูปใบที่ {$i} ต้องเป็น https");
        }
    }

    /**
     * 4️⃣ คำชวนที่หมุนมาแล้วต้องขึ้นไปอยู่บนการ์ด
     *
     * ถ้าไม่ขึ้น = ลูกค้าได้การ์ดถ้อยคำกลาง ๆ แทนสำนวนที่หมุนไว้ (และถ้าเผลอส่ง
     * ข้อความนำหน้าด้วย จะกลายเป็นถ้อยคำเดียวกัน 2 กล่องซ้อน)
     */
    public function test_คำชวนที่ส่งเข้าไปขึ้นบนหัวการ์ดใบฟรี(): void
    {
        $payload = $this->builder()->facebookEntry([
            'invite_text' => "🌙 ดวงประจำวันเกิดของวันนี้ แม่หมอเปิดไว้ให้ฟรีค่ะ\nกดรับได้เลยที่ปุ่มด้านล่าง ✨",
            'deep_price' => 39,
        ]);

        $this->assertNotNull($payload);

        $free = $payload['attachment']['payload']['elements'][0];

        $this->assertStringContainsString('ฟรี', $free['title'].$free['subtitle'], 'การ์ดใบฟรีต้องมีคำว่า "ฟรี"');
        $this->assertStringContainsString('ดวงประจำวันเกิดของวันนี้', $free['title']);
    }
}
