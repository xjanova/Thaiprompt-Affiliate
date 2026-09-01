<?php

namespace Tests\Unit\Services;

use App\Services\FortuneConversationService;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🌱 (2026-08-10, owner) "หลังจากรับดวงรายวันฟรีแล้ว อยากให้ชวนดูดวงละเอียดเนียน ๆ ด้วย"
 *
 * เดิมท้ายกล่องดวงรายวันเป็นประโยคตายตัว 2 แบบ (ไม่หมุนสำนวน / ไม่บอกว่าเป็นดวงรวม /
 * ยื่นปุ่ม 👑 VIP ให้ทุกคนเสมอแม้ตอน deep+celtic ปิด) — ตอนนี้รวมศูนย์ที่
 * buildDailyDeepInviteTail + pickDailySoftDeepInvite
 *
 * ไม่ต้องใช้ DB — เทสต์เฉพาะตัวประกอบข้อความ (pure) + ธงกันชวนซ้ำ (cache array driver)
 * ส่วนด่านที่อ่าน fortune_readings (dailyRecentlyPaid) ต้องมี MySQL จึงเทสต์ได้แค่ว่า
 * ตัวครอบ fail-open — ซึ่งเป็นพฤติกรรมที่ต้องการพอดี
 */
class FortuneDailySoftInviteTest extends TestCase
{
    protected FortuneConversationService $service;

    /** ทุกวันในสัปดาห์ × รู้/ไม่รู้วันเกิดเต็ม */
    protected const ALL_DAYS = [0, 1, 2, 3, 4, 5, 6];

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

    protected function invite(string $seed, int $dayIndex, bool $knowsFullBirthdate): string
    {
        return $this->invokeHidden('pickDailySoftDeepInvite', $seed, $dayIndex, $knowsFullBirthdate);
    }

    /**
     * ⭐ หัวใจของคำชวน — ต้องบอกตามตรงว่าดวงรายวันคือ "ดวงรวม" ของคนเกิดวันเดียวกัน
     *
     * ถ้าไม่มีประโยคนี้ ลูกค้าอ่านแล้วคิดว่า "ก็ได้ดวงไปแล้วนี่" → คำชวนกลายเป็น
     * การขายของที่เพิ่งแจกฟรีไปเมื่อกี้ นี่คือเหตุผลเดียวที่ทำให้ชวนต่อได้อย่างไม่เก้อ
     *
     * @test
     */
    public function ทุกสำนวนต้องบอกว่าเป็นดวงรวมและระบุวันเกิดในสัปดาห์(): void
    {
        $dayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

        foreach (self::ALL_DAYS as $dayIndex) {
            foreach ([true, false] as $knows) {
                // ไล่ seed ให้ครบทุกสำนวนในลิสต์ (4 สำนวนต่อสาย)
                for ($i = 0; $i < 24; $i++) {
                    $msg = $this->invite("seed-{$i}", $dayIndex, $knows);

                    $this->assertStringContainsString('ดวงรวม', $msg,
                        "ต้องบอกว่าเป็นดวงรวม (day={$dayIndex} knows=".var_export($knows, true).')');
                    $this->assertStringContainsString($dayNames[$dayIndex], $msg,
                        'ต้องระบุวันเกิดในสัปดาห์ที่ผูกกับบทความ');
                    $this->assertStringContainsString('เจ้าชะตา', $msg,
                        'สรรพนามตามกฎแม่หมอ (rule_fortune_address_fundhong_extras)');
                }
            }
        }
    }

    /**
     * 🚨 กฎแม่หมอหญิงเสมอ + ห้ามฝังตัวเลขราคา
     *
     * ราคาแอดมินแก้ได้จากหน้าตั้งค่า ฝังเลขในข้อความ = เพี้ยนทันทีที่แก้
     * (ปุ่ม 👑 VIP พาไป tier menu ซึ่งเป็นเจ้าของราคาตัวจริง)
     *
     * @test
     */
    public function ทุกสำนวนห้ามมีคำผู้ชายและห้ามบอกราคา(): void
    {
        foreach (self::ALL_DAYS as $dayIndex) {
            foreach ([true, false] as $knows) {
                for ($i = 0; $i < 24; $i++) {
                    $msg = $this->invite("seed-{$i}", $dayIndex, $knows);

                    foreach (['ครับ', 'ผม', 'ดิฉัน', 'หนู'] as $banned) {
                        $this->assertStringNotContainsString($banned, $msg, "ห้ามมีคำว่า {$banned}");
                    }

                    $this->assertDoesNotMatchRegularExpression('/\d+\s*(บาท|฿)/u', $msg,
                        'ห้ามฝังราคา — ให้ tier menu เป็นคนบอก');
                    $this->assertDoesNotMatchRegularExpression('/\d/u', $msg,
                        'สายนี้ไม่ควรมีตัวเลขเลย (กันราคา/จำนวนคำถามหลุดมาแบบเงียบ ๆ)');
                }
            }
        }
    }

    /**
     * 🐛 กันบั๊ก 2026-08-04 กลับมา — สายที่ยังไม่รู้วันเกิดเต็ม ห้ามสัญญาว่า
     *    "บอกวันเกิดมาแล้วจะดูให้ละเอียดกว่านี้"
     *
     * บทความรายวันผูกกับ *วันในสัปดาห์* → วันเกิดเต็มของคนเดิมให้ dayIndex เดิม
     * = บทความใบเดิมเป๊ะ ๆ คำสัญญานั้นจึงวนที่เดิมเสมอ ทำได้แค่ "ขอเก็บไว้ใช้ตอนเปิดไพ่"
     *
     * @test
     */
    public function สายยังไม่รู้วันเกิดต้องขอวันเกิดโดยไม่สัญญาว่าจะดูให้ละเอียดกว่าเดิม(): void
    {
        foreach (self::ALL_DAYS as $dayIndex) {
            for ($i = 0; $i < 24; $i++) {
                $msg = $this->invite("seed-{$i}", $dayIndex, false);

                $this->assertStringContainsString('วัน/เดือน/ปีเกิด', $msg,
                    'ต้องขอวันเกิดเต็มให้ชัด');
                $this->assertStringNotContainsString('ละเอียดกว่านี้', $msg,
                    'ห้ามสัญญาดวงที่ละเอียดขึ้นแลกกับวันเกิด — บทความรายวันให้ใบเดิมเสมอ');
            }
        }
    }

    /**
     * สำนวนต้องคงที่ต่อ "คนเดิม + วันเดิม" แต่หมุนได้จริงระหว่างคน
     *
     * ลูกค้าที่รับดวงทุกวันเห็นบรรทัดเดิมเป๊ะ = อ่านเป็นแบนเนอร์โฆษณา (ปัญหาที่มาแก้รอบนี้)
     *
     * @test
     */
    public function สำนวนคงที่ต่อคนต่อวันแต่หมุนได้ระหว่างคน(): void
    {
        foreach ([true, false] as $knows) {
            $a1 = $this->invite('psid-A:2026-08-10', 3, $knows);
            $a2 = $this->invite('psid-A:2026-08-10', 3, $knows);

            $this->assertSame($a1, $a2, 'คนเดิม วันเดิม ต้องได้ข้อความเดิม (ขอดูซ้ำไม่สลับไปมา)');

            $variants = [];
            for ($i = 0; $i < 60; $i++) {
                $variants[] = $this->invite("psid-{$i}:2026-08-10", 3, $knows);
            }

            $this->assertGreaterThanOrEqual(3, count(array_unique($variants)),
                'ต้องหมุนสำนวนได้จริง ไม่ใช่ประโยคเดียวตลอด');
        }
    }

    /**
     * 2 สายต้องเป็นคนละชุดข้อความ — คนที่ให้วันเกิดครบแล้ว ห้ามโดนถามวันเกิดซ้ำ
     * (บทเรียนเดิม: ถามซ้ำกับคนที่เพิ่งให้ไว้ = บอทเหมือนไม่จำอะไรเลย)
     *
     * @test
     */
    public function สายรู้วันเกิดแล้วต้องไม่ขอวันเกิดซ้ำ(): void
    {
        foreach (self::ALL_DAYS as $dayIndex) {
            for ($i = 0; $i < 24; $i++) {
                $msg = $this->invite("seed-{$i}", $dayIndex, true);

                $this->assertStringNotContainsString('วัน/เดือน/ปีเกิด', $msg,
                    'รู้วันเกิดครบแล้ว ห้ามขอซ้ำ');
            }
        }
    }

    // 🗑️ (2026-09-01) ลบเทสต์ธงกันชวนซ้ำ — markDailyInviteShownToday ถูกลบแล้ว
    //   (call site เดียวถูกถอดตั้งแต่ 2026-08-19 เทสต์นี้ล็อกพฤติกรรมของโค้ดตายอยู่)

    /**
     * ด่านเสริมต้อง fail-open — อ่าน settings/DB ไม่ได้ต้องเงียบ ไม่ใช่โยน exception
     * ใส่เส้น webhook (ลูกค้าต้องได้ดวงฟรีครบเสมอ แม้หางคำชวนจะหายไป)
     *
     * instance นี้สร้างแบบข้ามคอนสตรัคเตอร์ = ไม่มี $settings เลย → จำลองสภาพพัง
     *
     * @test
     */
    public function หางคำชวนต้อง_fail_open_เป็นเงียบทั้งข้อความและปุ่ม(): void
    {
        $tail = $this->invokeHidden('buildDailyDeepInviteTail', 'facebook', 'psid-x', 1, true);

        $this->assertIsArray($tail);
        $this->assertNull($tail['text'], 'พังต้องไม่มีคำชวน');
        $this->assertSame([], $tail['quick_replies'], 'พังต้องไม่ยื่นปุ่มที่กดแล้วอาจไม่มีของ');

        $this->assertFalse(
            $this->invokeHidden('dailyUpgradeInviteAllowed', 'facebook', 'psid-x'),
            'อ่านสวิตช์บริการไม่ได้ → ห้ามชวน'
        );
    }

    /**
     * 🛡️ signature guard — หางคำชวนต้องรับ $platform/$userId ไว้เช็คขาประจำ + กันชวนซ้ำ
     *
     * ถ้าพารามิเตอร์หายตอน refactor ด่าน "เพิ่งจ่ายใน 7 วัน" กับ "วันนี้ชวนไปแล้ว"
     * จะเงียบหายไปโดยไม่มีอะไรฟ้อง (คำชวนยังออกเหมือนเดิม = มองไม่เห็นว่าพัง)
     *
     * @test
     */
    public function หางคำชวนต้องยังรับ_platform_และ_user_id(): void
    {
        $m = new ReflectionMethod(FortuneConversationService::class, 'buildDailyDeepInviteTail');
        $params = $m->getParameters();

        $this->assertCount(5, $params,
            'ต้องมี 5 พารามิเตอร์: platform, userId, dayIndex, knowsFullBirthdate, articleDelivered');
        $this->assertSame('platform', $params[0]->getName());
        $this->assertSame('userId', $params[1]->getName());
        $this->assertTrue($params[1]->allowsNull(), '$userId ต้องรับ null ได้ (เส้นพิมพ์วันเกิดเองไม่มี user id)');
        $this->assertSame('dayIndex', $params[2]->getName());
        $this->assertSame('knowsFullBirthdate', $params[3]->getName());

        // 🐛 ตัวกันโกหก: ถ้าพารามิเตอร์นี้หายตอน refactor คำชวน "ดวงที่แม่หมออ่านให้เมื่อกี้..."
        //    จะกลับไปต่อท้ายข้อความ "วันนี้ยังไม่มีบทความ" อีก โดยไม่มีอะไรฟ้อง
        $this->assertSame('articleDelivered', $params[4]->getName());
        $this->assertTrue($params[4]->isOptional(), 'ต้อง optional เพื่อไม่ทำผู้เรียกเดิมพัง');
    }

    /**
     * 🌿 ประโยคคุยต่อของขาประจำ ต้อง "ไม่ขาย" จริง ๆ
     *
     * ใช้ตอนที่เพิ่งจ่ายไปใน 7 วัน / deep+celtic ปิด — ถ้ามีคำขายหลุดเข้ามา
     * เท่ากับตื๊อขาประจำ ซึ่งเป็นสิ่งที่ด่านนี้มีไว้กันพอดี
     *
     * @test
     */
    public function ประโยคคุยต่อของขาประจำต้องไม่มีคำขาย(): void
    {
        for ($i = 0; $i < 24; $i++) {
            $msg = $this->invokeHidden('pickRegularWarmFollowUp', "psid-{$i}");

            foreach (['บูชาครู', 'ค่าครู', 'เปิดไพ่', 'เชิงลึก', 'ละเอียด', 'VIP', 'บาท'] as $sales) {
                $this->assertStringNotContainsString($sales, $msg, "ห้ามมีคำขาย: {$sales}");
            }

            foreach (['ครับ', 'ผม', 'ดิฉัน'] as $banned) {
                $this->assertStringNotContainsString($banned, $msg, "ห้ามมีคำว่า {$banned}");
            }

            $this->assertDoesNotMatchRegularExpression('/\d/u', $msg, 'ห้ามมีตัวเลข');
            $this->assertNotSame('', trim($msg), 'ต้องมีประโยคคุยต่อเสมอ — ห้ามปล่อยบทสนทนาตาย');
        }
    }

    /**
     * dayIndex นอกช่วง 0-6 (ข้อมูลเพี้ยน) ต้องยังได้ประโยคที่อ่านรู้เรื่อง ไม่ใช่ช่องว่างโหว่
     *
     * @test
     */
    public function วันเกิดนอกช่วงต้องยังอ่านรู้เรื่อง(): void
    {
        foreach ([-1, 7, 99] as $bad) {
            foreach ([true, false] as $knows) {
                $msg = $this->invite('seed-bad', $bad, $knows);

                $this->assertStringContainsString('ดวงรวม', $msg);
                $this->assertStringContainsString('วันเดียวกัน', $msg, 'ต้องมีสำนวนสำรองแทนชื่อวัน');
                $this->assertStringNotContainsString('เกิดวันทั้งหมด', $msg, 'ห้ามมีช่องว่างจากชื่อวันที่หายไป');
            }
        }
    }
}
