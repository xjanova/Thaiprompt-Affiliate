<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\FacebookWebhookController;
use App\Models\FortuneUserCredit;
use App\Services\Fortune\FortuneGreetingService;
use App\Services\FortuneConversationService;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🌙 (2026-08-04) DM ดวงรายวันต้อง "จำ" คนที่ตอบเราด้วยปุ่มวันเกิดได้
 *
 * เคสที่ owner รายงาน: "DM ไปหาคนที่มีข้อมูลวันเกิดอยู่แล้ว ควรส่งคำทำนายตามวันเกิดให้เลย"
 *
 * ต้นเหตุ: rememberDailyBirthInfo() เก็บ `birth_date` เฉพาะตอนลูกค้า**พิมพ์** ว/ด/ป เต็ม
 * ส่วนคนที่**กดปุ่ม** 7 วันเกิดได้แค่ `birth_day` — แต่ทุกด่านตัดสินด้วย findLatestBirthdate()
 * ซึ่งอ่านแต่ `birth_date` ⇒ prod 2026-08-04 ตกหล่น 416 จาก 493 คน (84%)
 * ทั้งที่บทความรายวันเลือกด้วย "วันในสัปดาห์" อย่างเดียว = `birth_day` พอเสิร์ฟอยู่แล้ว
 *
 * ไม่ต้องใช้ DB — เทสต์ส่วนบริสุทธิ์ (normalizeBirthDayIndex) + ยาม wiring ที่อ่านซอร์สจริง
 * (findBirthDayIndex เองแตะ DB ต้องมี MySQL จึงเทสต์ได้แค่ว่ามัน fail-safe เป็น null)
 */
class FortuneDailyBirthDayIndexTest extends TestCase
{
    /**
     * อ่านซอร์สจริงของเมธอด — ใช้ยืนยัน "ด่านนี้ยังถามคำถามที่ถูกอยู่ไหม"
     */
    protected function sourceOf(string $class, string $method): string
    {
        $m = new ReflectionMethod($class, $method);
        $lines = file($m->getFileName());

        return implode('', array_slice(
            $lines,
            $m->getStartLine() - 1,
            $m->getEndLine() - $m->getStartLine() + 1
        ));
    }

    /**
     * ค่า index วันเกิดที่ใช้ได้คือ 0-6 เท่านั้น นอกนั้นต้องแปลว่า "ไม่รู้"
     *
     * ปล่อยค่านอกช่วงผ่านไป = array index ระเบิดตอนแปลงเป็นชื่อวันปลายทาง
     *
     * @test
     */
    public function index_วันเกิดต้องอยู่ในช่วง_0_ถึง_6_เท่านั้น(): void
    {
        // ใช้ได้ — รวมค่าที่มาจาก DB เป็น string
        $this->assertSame(0, FortuneUserCredit::normalizeBirthDayIndex(0), 'อาทิตย์ = 0 ต้องผ่าน (ห้ามตกเพราะ falsy)');
        $this->assertSame(3, FortuneUserCredit::normalizeBirthDayIndex(3));
        $this->assertSame(6, FortuneUserCredit::normalizeBirthDayIndex(6));
        $this->assertSame(3, FortuneUserCredit::normalizeBirthDayIndex('3'));

        // ใช้ไม่ได้ → ต้องเป็น null (กลับไปถามวันเกิด ดีกว่าส่งดวงของคนอื่น)
        $this->assertNull(FortuneUserCredit::normalizeBirthDayIndex(null));
        $this->assertNull(FortuneUserCredit::normalizeBirthDayIndex(''));
        $this->assertNull(FortuneUserCredit::normalizeBirthDayIndex('พุธ'));
        $this->assertNull(FortuneUserCredit::normalizeBirthDayIndex(-1));
        $this->assertNull(FortuneUserCredit::normalizeBirthDayIndex(7), 'dayOfWeekIso ของวันอาทิตย์ = 7 ต้องไม่ผ่าน');
    }

    /**
     * ⭐ หัวใจของการแก้รอบนี้ — teaser ต้องนับคนที่มีแค่ birth_day ว่า "รู้จัก"
     *
     * ถ้าใครเผลอเปลี่ยนกลับไปใช้ findLatestBirthdate ลูกค้า 84% จะโดนถามวันเกิด
     * ซ้ำทุกวันเหมือนบอทไม่เคยจำอะไรเลย — บั๊กที่ไม่มีอะไรฟ้องบนจอ
     *
     * @test
     */
    public function teaser_ต้องตัดสินด้วย_find_birth_day_index_ไม่ใช่วันเกิดเต็ม(): void
    {
        $src = $this->sourceOf(FortuneGreetingService::class, 'buildDailyReadyTeaser');

        $this->assertStringContainsString('findBirthDayIndex', $src);
        $this->assertStringNotContainsString(
            'findLatestBirthdate(',
            $src,
            'buildDailyReadyTeaser กลับไปใช้วันเกิดเต็ม = คนที่ตอบด้วยปุ่มถูกถามซ้ำทุกวัน'
        );
    }

    /**
     * ปุ่ม "ดูดวงวันนี้เลย" ต้องใช้เกณฑ์เดียวกับตอนยื่นปุ่ม
     *
     * teaser ยื่นปุ่มด้วยเกณฑ์กว้าง แต่ตัวรับกดใช้เกณฑ์แคบ = กดแล้วโดนถามวันเกิดใหม่
     * → ปุ่มตาย ซึ่งเสียความน่าเชื่อถือมากกว่าไม่มีปุ่มเลย
     *
     * @test
     */
    public function ปุ่มดูดวงวันนี้ต้องใช้เกณฑ์เดียวกับตอนยื่นปุ่ม(): void
    {
        $src = $this->sourceOf(FacebookWebhookController::class, 'handleDailyShowMine');

        $this->assertStringContainsString('findBirthDayIndex', $src);
        $this->assertStringNotContainsString(
            'findLatestBirthdate(',
            $src,
            'handleDailyShowMine ต้องรู้จักคนกลุ่มเดียวกับที่ buildDailyReadyTeaser ยื่นปุ่มให้'
        );
    }

    /**
     * ตอบรับสั้น ๆ ("เอาค่ะ") แทนการกดปุ่ม ก็ต้องรู้จักคนกลุ่มเดียวกัน
     *
     * ไม่งั้นลูกค้าตอบรับแล้วบอทเงียบ — ทางตันแบบเดียวกับที่ 2026-08-01 เพิ่งแก้ไป
     *
     * @test
     */
    public function ตอบรับสั้นๆ_ต้องรู้จักคนที่ตอบด้วยปุ่มด้วย(): void
    {
        $src = $this->sourceOf(FortuneConversationService::class, 'resolveDayIndexFromShortYes');

        $this->assertStringContainsString('findBirthDayIndex', $src);
        $this->assertStringNotContainsString('findLatestBirthdate(', $src);
    }

    /**
     * 🚨 กับดักฝั่งตรงข้าม — "รู้ ว/ด/ป ครบไหม" ต้อง **ไม่** ถูกเปลี่ยนตาม
     *
     * findBirthDayIndex นับคนที่รู้แค่ "วันพุธ" ว่ารู้ด้วย ถ้าเอามาใช้ตรงนี้ บอทจะพูดว่า
     * "แม่หมอจดวันเกิดของเจ้าชะตาไว้แล้วนะคะ" ใส่คนที่ยังไม่เคยให้ ว/ด/ป
     * แล้วพอซื้อ Deep จริงต้องถามใหม่ = บอทโกหกลูกค้า
     *
     * @test
     */
    public function ด่านรู้วันเกิดเต็มต้องยังใช้วันเกิดเต็มเท่านั้น(): void
    {
        $src = $this->sourceOf(FortuneConversationService::class, 'dailyKnowsFullBirthdate');

        $this->assertStringContainsString('findLatestBirthdate', $src);
        $this->assertStringNotContainsString(
            'findBirthDayIndex',
            $src,
            'dailyKnowsFullBirthdate ต้องเป็น "รู้ ว/ด/ป ครบ" เท่านั้น ไม่ใช่รู้แค่วันในสัปดาห์'
        );
    }

    /**
     * ตัวประกอบกล่องดวงรับ index (0-6) ไม่ใช่ Carbon แล้ว
     *
     * signature guard — ถ้ามีคนเปลี่ยนกลับไปรับ Carbon คนที่มีแต่ birth_day
     * จะส่งเข้าไม่ได้ แล้วการแก้รอบนี้จะย้อนกลับไปเงียบ ๆ
     *
     * @test
     */
    public function กล่องดวงรายวันต้องรับ_index_วันเกิดไม่ใช่_carbon(): void
    {
        $m = new ReflectionMethod(FortuneGreetingService::class, 'buildTodayBoxForBirthday');
        $params = $m->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('int', (string) $params[1]->getType(), 'พารามิเตอร์ที่ 2 ต้องเป็น index วันเกิด (0-6)');
    }

    /**
     * fail-safe — หา index ไม่ได้ (ไม่มี DB / ไม่มีข้อมูล) ต้องคืน null ไม่ใช่ throw
     *
     * ด่านนี้อยู่บนเส้น webhook — throw = ลูกค้าไม่ได้คำตอบเลย
     *
     * @test
     */
    public function หาวันเกิดไม่เจอต้องคืน_null_ไม่ใช่ระเบิด(): void
    {
        $this->assertNull(FortuneUserCredit::findBirthDayIndex('psid-ที่ไม่มีจริง-'.uniqid(), 'facebook'));
    }
}
