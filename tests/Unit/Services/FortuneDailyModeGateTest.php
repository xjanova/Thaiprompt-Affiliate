<?php

namespace Tests\Unit\Services;

use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneBotMode;
use App\Services\FortuneConversationService;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * ทดสอบตัวตีความคำตอบของโหมด DM ดูดวงรายวัน (DailyHoroscopeModeTrait)
 *
 * ทดสอบเฉพาะฟังก์ชันบริสุทธิ์ที่ไม่แตะ DB/Cache — ส่วน guard ที่แตะ DB
 * (จ่ายเงินแล้ว/บิลค้าง/ธง pending) ต้องทดสอบด้วย Feature test แยก
 */
class FortuneDailyModeGateTest extends TestCase
{
    protected FortuneConversationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = (new ReflectionClass(FortuneConversationService::class))
            ->newInstanceWithoutConstructor();
    }

    protected function invokeMethod(string $method, mixed ...$args): mixed
    {
        $m = new ReflectionMethod($this->service, $method);
        $m->setAccessible(true);

        return $m->invoke($this->service, ...$args);
    }

    /**
     * ชื่อวันในสัปดาห์ → index 0-6 (ต้องตรงกับคอลัมน์ birth_day)
     *
     * @test
     */
    public function จับชื่อวันในสัปดาห์ได้ถูกต้อง(): void
    {
        $expect = [
            'อาทิตย์' => 0, 'วันอาทิตย์' => 0, 'เกิดวันอาทิตย์ค่ะ' => 0,
            'จันทร์' => 1, 'วันจันทร์' => 1, 'เกิดวันจันทร์' => 1,
            'อังคาร' => 2, 'วันอังคาร' => 2,
            'พุธ' => 3, 'วันพุธ' => 3,
            'พฤหัสบดี' => 4, 'พฤหัส' => 4, 'วันพฤหัสบดีค่ะ' => 4,
            'ศุกร์' => 5, 'วันศุกร์' => 5,
            'เสาร์' => 6, 'วันเสาร์' => 6,
        ];

        foreach ($expect as $text => $index) {
            $this->assertSame($index, $this->invokeMethod('detectThaiDayName', $text), "ข้อความ: {$text}");
        }
    }

    /**
     * ข้อความที่ไม่ใช่ชื่อวัน ต้องไม่ถูกจับ
     *
     * @test
     */
    public function ไม่จับข้อความที่ไม่ใช่ชื่อวัน(): void
    {
        foreach (['สวัสดีค่ะ', 'ขอบคุณค่ะ', '0872804575', 'อยากดูดวง', ''] as $text) {
            $this->assertNull($this->invokeMethod('detectThaiDayName', $text), "ข้อความ: {$text}");
        }
    }

    /**
     * ด่านเช็คเร็ว "หน้าตาเป็นวันที่" — กันข้อความทั่วไปไปโดน AI fallback (0.7-7.5 วิ)
     *
     * @test
     */
    public function ด่านเช็คเร็วต้องแยกวันที่ออกจากข้อความทั่วไป(): void
    {
        // ✅ หน้าตาเป็นวันที่
        foreach (['12/05/2530', '12-5-30', '12.5.2530', '12 5 2530', '15 มีนาคม 2538', '15 มี.ค. 38'] as $t) {
            $this->assertTrue($this->invokeMethod('looksLikeDateInput', $t), "ควรผ่าน: {$t}");
        }

        // ❌ ไม่ใช่วันที่ — ต้องไม่เรียก parser ตัวเต็ม
        foreach (['สวัสดีค่ะ', 'ขอบคุณมากนะคะ', 'อยากรู้ดวงจัง', '0872804575'] as $t) {
            $this->assertFalse($this->invokeMethod('looksLikeDateInput', $t), "ไม่ควรผ่าน: {$t}");
        }
    }

    /**
     * ลูกค้าเปลี่ยนเรื่อง — ต้องคืนธงแล้วปล่อย flow เดิม ห้ามดันวันเกิดต่อ
     *
     * @test
     */
    public function จับคำที่ลูกค้าเปลี่ยนเรื่องได้(): void
    {
        foreach (['โอนแล้วค่ะ', 'ขอคุยกับคน', 'ยกเลิก', 'อยากดูดวง', 'เช็คสถานะ'] as $t) {
            $this->assertTrue($this->invokeMethod('looksLikeDailyEscape', $t), "ควรจับ: {$t}");
        }

        foreach (['วันจันทร์', '12/05/2530', 'สวัสดีค่ะ'] as $t) {
            $this->assertFalse($this->invokeMethod('looksLikeDailyEscape', $t), "ไม่ควรจับ: {$t}");
        }
    }

    /**
     * "ขอบคุณ" ล้วน → ตอบคำอวยพร · มีคำถามตามหลัง → ต้องไม่กิน
     *
     * บทเรียนเคส R4543: "ขอบคุณค่ะ แล้วเรื่องงานล่ะ" เคยถูกกินเป็น ack
     * ทำให้คำถามของลูกค้าหายไป
     *
     * @test
     */
    public function จับคำขอบคุณล้วนได้และไม่กินคำถามที่ตามหลัง(): void
    {
        // ✅ ขอบคุณล้วน → ตอบคำอวยพรได้
        foreach ([
            'ขอบคุณ', 'ขอบคุณค่ะ', 'ขอบคุณครับ', 'ขอบคุณมากค่ะ', 'ขอบคุณค่ะแม่หมอ',
            'ขอบคุณมากๆนะคะ', 'ขอบพระคุณค่ะ', 'ขอบใจจ้า', 'thank you', 'thanks',
        ] as $t) {
            $this->assertTrue($this->invokeMethod('looksLikePureThanks', $t), "ควรจับ: {$t}");
        }

        // ❌ มีเนื้อหาตามหลัง → ห้ามกิน (คำถามลูกค้าต้องไหลไป flow ปกติ)
        foreach ([
            'ขอบคุณค่ะ แล้วเรื่องงานล่ะ',
            'ขอบคุณค่ะ อยากถามเรื่องความรัก',
            'ขอบคุณนะคะ ดูดวงอีกได้ไหม',
            'สวัสดีค่ะ',
            '',
        ] as $t) {
            $this->assertFalse($this->invokeMethod('looksLikePureThanks', $t), "ไม่ควรจับ: {$t}");
        }
    }

    /**
     * ปุ่ม 7 วันเกิด — payload ต้องตรงกับ index และมีครบ 7 ปุ่ม
     *
     * @test
     */
    public function ปุ่มเจ็ดวันเกิดต้องครบและpayloadตรงindex(): void
    {
        $buttons = FortuneConversationService::dailyBirthdayQuickReplies();

        $this->assertCount(7, $buttons);

        foreach ($buttons as $index => $btn) {
            $this->assertSame('DAILY_BDAY_'.$index, $btn['payload']);
            $this->assertSame('text', $btn['content_type']);
            // FB จำกัด title 20 ตัวอักษร
            $this->assertLessThanOrEqual(20, mb_strlen($btn['title']), "ปุ่มยาวเกิน: {$btn['title']}");
        }
    }

    /**
     * 🎁 (2026-08-01) ด่านขาเข้าต้องเปิดในโหมด classic ด้วย
     *
     * เพราะปุ่มรับดวงประจำวันเกิดถูกยื่นให้ตอนลูกค้าขอดูฟรีแต่สิทธิ์หมด — ซึ่งเกิดได้
     * ทุกโหมด ถ้าด่านนี้ยังบังคับ daily อยู่ ปุ่มในโหมด classic จะกดแล้วเงียบ
     *
     * @test
     */
    public function ด่านตอบวันเกิดต้องเปิดทุกโหมดยกเว้นtransfer(): void
    {
        $expect = [
            FortuneBotMode::MODE_CLASSIC => true,
            FortuneBotMode::MODE_DAILY => true,
            FortuneBotMode::MODE_TRANSFER => false,
        ];

        foreach ($expect as $mode => $allowed) {
            $botMode = new FortuneBotMode(new FortuneTellingSetting(['fortune_bot_mode' => $mode]));

            $this->assertSame(
                $allowed,
                $botMode->dailyReplyAllowedFor('facebook', 'PSID_1'),
                "โหมด {$mode} บน facebook"
            );

            // LINE ไม่เคยมี DM ชวนบอกวันเกิด → ห้ามแย่งข้อความทุกโหมด
            $this->assertFalse(
                $botMode->dailyReplyAllowedFor('line', 'U_1'),
                "โหมด {$mode} บน line ต้องไม่ผ่าน"
            );

            // ไม่มี user id = ไม่มีใครให้ตอบ
            $this->assertFalse(
                $botMode->dailyReplyAllowedFor('facebook', ''),
                "โหมด {$mode} user id ว่าง ต้องไม่ผ่าน"
            );
        }
    }

    /**
     * 👍 "เอาค่ะ" = เท่ากับกดปุ่มดูดวงวันนี้ · "ไม่เอา" ต้องไม่ใช่
     *
     * เทียบตรงตัวเท่านั้น — ถ้าเผลอเปลี่ยนเป็น substring วันหนึ่ง "ไม่เอาค่ะ" จะกลายเป็น
     * การตอบรับ แล้วบอทจะยัดคำทำนายใส่คนที่เพิ่งปฏิเสธ
     *
     * @test
     */
    public function จับคำตอบรับสั้นได้และไม่กินคำปฏิเสธ(): void
    {
        // ✅ ตอบรับล้วน
        foreach ([
            'เอา', 'เอาค่ะ', 'เอาเลยค่ะ', 'ดูเลย', 'ดูเลยครับ', 'อยากดู', 'ขอดูหน่อย',
            'ตกลง', 'ได้เลยค่ะ', 'โอเค', 'ใช่ค่ะ', 'สนใจ', 'ส่งมาเลย', 'ok', 'yes',
        ] as $t) {
            $this->assertTrue($this->invokeMethod('looksLikeShortYes', $t), "ควรจับ: {$t}");
        }

        // ❌ ห้ามจับ — ปฏิเสธ / คำลงท้ายเปล่า ๆ / มีเนื้อหาอื่น
        foreach ([
            'ไม่เอา', 'ไม่เอาค่ะ', 'ไม่ดู', 'ไม่สนใจ', 'ยังไม่เอา',
            'ค่ะ', 'ครับ', 'จ้า', '',
            'เอาไว้ก่อนนะคะ', 'ขอดูราคาก่อน', 'อยากดูดวงความรัก',
        ] as $t) {
            $this->assertFalse($this->invokeMethod('looksLikeShortYes', $t), "ไม่ควรจับ: {$t}");
        }
    }

    /**
     * คำชวนบอกวันเกิดรับดวงฟรี — ต้องไม่มีตัวเลขราคาหลุดเข้าไป
     *
     * ลูกค้าเพิ่งขอของฟรี การเด้งราคาใส่คือสิ่งที่ฟีเจอร์นี้แก้อยู่พอดี
     *
     * @test
     */
    public function คำชวนรับดวงฟรีต้องไม่มีราคา(): void
    {
        foreach (['PSID_A', 'PSID_B', 'PSID_C', 'PSID_D', 'PSID_E'] as $uid) {
            $line = $this->invokeMethod('pickDailyFreeOffer', $uid);

            $this->assertIsString($line);
            $this->assertNotSame('', trim($line));
            $this->assertStringContainsString('ฟรี', $line, "ต้องบอกว่าฟรี: {$line}");
            $this->assertDoesNotMatchRegularExpression('/\d/u', $line, "มีตัวเลข (ราคา?) หลุดมา: {$line}");
        }
    }
}
