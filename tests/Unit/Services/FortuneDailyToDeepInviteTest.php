<?php

namespace Tests\Unit\Services;

use App\Services\FortuneConversationService;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 💎 (2026-08-04, owner) หลังรับดวงรายวันแล้ว ส่งวันเดือนปีเกิดเต็มมา = ต้องชวนดูเชิงลึก
 *
 * เคสที่ owner รายงาน: ท้ายกล่องดวงรายวันบอกว่า "ถ้าบอกวัน/เดือน/ปีเกิดเต็ม ๆ มาด้วย
 * แม่หมอจะดูให้ละเอียดกว่านี้ได้อีกเยอะ" → ลูกค้าทำตาม → บอทกลับส่ง **บทความใบเดิม**
 * กลับไปอีก (เพราะวันเกิดนั้นให้ dayIndex เดิม = บทความเดิม) → คำสัญญาวนที่เดิม
 *
 * ไม่ต้องใช้ DB — เทสต์เฉพาะตัวประกอบข้อความ (pure) + สัญญาของ signature
 * (ตัว maybeInviteDeepAfterDailySent อ่าน fortune_user_credits จริง ต้องมี MySQL
 *  จึงเทสต์ได้แค่ว่ามัน fail-open เมื่อบูตไม่ได้ — ซึ่งเป็นพฤติกรรมที่ต้องการพอดี)
 */
class FortuneDailyToDeepInviteTest extends TestCase
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
     * ข้อความชวนต้องยืนยันว่า "ได้วันเกิดแล้ว" + บอกว่าดวงรายวันคือดวงรวม
     *
     * ถ้าไม่บอกว่ารายวัน = ดวงรวมของคนเกิดวันเดียวกัน ลูกค้าจะรู้สึกว่า "ก็ได้ไปแล้วนี่"
     * แล้วคำชวนจะกลายเป็นการขายของเดิมซ้ำ
     *
     * @test
     */
    public function ข้อความชวนต้องทวนวันเกิดและแยกดวงรวมออกจากดวงเฉพาะตัว(): void
    {
        // 12 พ.ค. 2530 (ค.ศ. 1987-05-12) = วันอังคาร
        $msg = $this->invokeHidden('buildDailyToDeepInvite', 'psid-1', '1987-05-12', 2);

        $this->assertStringContainsString('12 พฤษภาคม 2530', $msg, 'ต้องทวนวันเกิดเป็น พ.ศ. ให้ลูกค้าตรวจได้');
        $this->assertStringContainsString('อังคาร', $msg, 'ต้องบอกวันเกิดในสัปดาห์ที่ผูกกับบทความ');
        $this->assertStringContainsString('ดวงรวม', $msg, 'ต้องอธิบายว่าดวงรายวันไม่ใช่ดวงเฉพาะตัว');
        $this->assertStringContainsString('เจ้าชะตา', $msg, 'สรรพนามตามกฎแม่หมอ');
    }

    /**
     * 🚨 กฎแม่หมอ — หญิงเสมอ ห้าม ครับ/ผม และห้ามฝังตัวเลขราคา
     *
     * ราคาแอดมินแก้ได้จากหน้าตั้งค่า ฝังเลขในข้อความ = เพี้ยนทันทีที่แก้
     * (ให้ปุ่ม 💎 พาไป tier menu ซึ่งเป็นเจ้าของราคาตัวจริง)
     *
     * @test
     */
    public function ข้อความชวนห้ามมีคำผู้ชายและห้ามบอกราคา(): void
    {
        foreach ([0, 1, 2, 3, 4, 5, 6] as $dayIndex) {
            $msg = $this->invokeHidden('buildDailyToDeepInvite', 'psid-'.$dayIndex, '1987-05-12', $dayIndex);

            foreach (['ครับ', 'ผม', 'ดิฉัน'] as $banned) {
                $this->assertStringNotContainsString($banned, $msg, "ห้ามมีคำว่า {$banned}");
            }

            $this->assertDoesNotMatchRegularExpression(
                '/\d+\s*(บาท|฿)/u',
                $msg,
                'ห้ามฝังราคา — ให้ tier menu เป็นคนบอก'
            );
        }
    }

    /**
     * สำนวนต้องคงที่ต่อ "คนเดิม + วันเดิม" (ไม่สลับไปมาถ้าลูกค้าส่งซ้ำในวันเดียวกัน)
     * แต่ต้องหมุนได้จริงเมื่อเป็นคนละคน
     *
     * @test
     */
    public function สำนวนคงที่ต่อคนต่อวันแต่หมุนได้ระหว่างคน(): void
    {
        $a1 = $this->invokeHidden('buildDailyToDeepInvite', 'psid-A', '1987-05-12', 2);
        $a2 = $this->invokeHidden('buildDailyToDeepInvite', 'psid-A', '1987-05-12', 2);

        $this->assertSame($a1, $a2, 'คนเดิม วันเดิม ต้องได้ข้อความเดิม');

        $variants = [];
        for ($i = 0; $i < 40; $i++) {
            $variants[] = $this->invokeHidden('buildDailyToDeepInvite', 'psid-'.$i, '1987-05-12', 2);
        }

        $this->assertGreaterThan(1, count(array_unique($variants)), 'ต้องหมุนสำนวนได้ ไม่ใช่ประโยคเดียวตลอด');
    }

    /**
     * 🛡️ buildDailyReadingForDetectedBirthdate ต้องรับ $userId ไว้จด "วันนี้ส่งดวงให้แล้ว"
     *
     * ถ้าพารามิเตอร์นี้หายไปตอน refactor ธงจะไม่ถูกจด → ลูกค้าพิมพ์วันเกิดซ้ำ
     * จะได้บทความใบเดิมกลับไปอีก = บั๊กเดิมกลับมาเงียบ ๆ โดยไม่มีอะไรฟ้อง
     *
     * @test
     */
    public function ตัวส่งดวงจากวันเกิดต้องยังรับ_user_id_ไว้จดธง(): void
    {
        $m = new ReflectionMethod(FortuneConversationService::class, 'buildDailyReadingForDetectedBirthdate');
        $params = $m->getParameters();

        $this->assertCount(3, $params, 'ต้องมี 3 พารามิเตอร์: birthDate, userProfile, userId');
        $this->assertSame('userId', $params[2]->getName());
        $this->assertTrue($params[2]->isOptional(), 'ต้อง optional เพื่อไม่ทำผู้เรียกเดิมพัง');
    }

    /**
     * ด่านเสริมต้อง fail-open — บูตไม่ได้/อ่านข้อมูลไม่ได้ ต้องคืน null
     * เพื่อให้ flow เดิมทำงานต่อ ห้ามโยน exception ใส่เส้น webhook
     *
     * @test
     */
    public function ด่านชวนเชิงลึกต้อง_fail_open_ไม่โยน_exception(): void
    {
        // instance นี้ไม่มี $settings (สร้างแบบข้าม constructor) = จำลองสภาพพัง
        $result = $this->invokeHidden('maybeInviteDeepAfterDailySent', 'psid-x', '1987-05-12');

        $this->assertNull($result, 'พังต้องเงียบและปล่อย flow เดิม ไม่ใช่บล็อกลูกค้า');
    }
}
