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

    /**
     * 🇹🇭 (2026-08-22) คำลงท้ายสะกดเพี้ยน ต้องยังอ่านออก
     *
     * เคสจริง Phensri Paopluk (PSID 27674940652154887, 17:22 น.) พิมพ์ "วันศุกร์ค้ะ"
     * แล้วไม่ได้ดวงรายวัน — "ค้ะ" ใช้ไม้โท แต่ลิสต์คำลงท้ายมีแค่ "ค่ะ" ไม้เอก
     * วันเดียวกันเจออีก 2 คนตกด้วยเหตุตระกูลเดียวกัน
     *
     * @test
     */
    public function คำลงท้ายสะกดเพี้ยนต้องยังอ่านออก(): void
    {
        // เคสจริงจาก prod 2026-08-22
        $this->assertSame(5, $this->invokeHidden('resolveBirthDayNameIndex', 'วันศุกร์ค้ะ'), 'ไม้โทแทนไม้เอก');
        $this->assertSame(5, $this->invokeHidden('resolveBirthDayNameIndex', 'วันศุกร์ฅรับ'), 'ฅ U+0E05 แทน ค U+0E04');

        // ตระกูลเดียวกันที่ต้องรอดไปด้วย (มิติวรรณยุกต์ + อักษรสับ)
        $this->assertSame(3, $this->invokeHidden('resolveBirthDayNameIndex', 'พุธค๊ะ'));
        $this->assertSame(0, $this->invokeHidden('resolveBirthDayNameIndex', 'อาทิตย์ค๊ะ'));
        $this->assertSame(5, $this->invokeHidden('resolveBirthDayNameIndex', 'ศุกร์คร้บ'), 'ตก ั');
        $this->assertSame(1, $this->invokeHidden('resolveBirthDayNameIndex', 'จันทร์ค้าบ'));
        $this->assertSame(6, $this->invokeHidden('resolveBirthDayNameIndex', 'เสาร์ครับผม'));
        $this->assertSame(4, $this->invokeHidden('resolveBirthDayNameIndex', 'พฤหัสบดีเจ้าค่ะ'));

        // looksLikeStandaloneDayName ต้องเห็นตรงกัน (ใช้ลิสต์ร่วมกันแล้ว)
        $this->assertTrue($this->invokeHidden('looksLikeStandaloneDayName', 'วันศุกร์ค้ะ'));
        $this->assertTrue($this->invokeHidden('looksLikeStandaloneDayName', 'วันศุกร์ฅรับ'));
    }

    /**
     * 🛡️ (2026-08-22) เปิดกว้างที่คำลงท้ายแล้ว ด่านที่เหลือต้องไม่อ่อนลงสักด่าน
     *
     * ตัวปอกคำลงท้ายแตะแค่ "หาง" — เศษที่เหลือยังต้องเป็นชื่อวันเต็มคำเหมือนเดิม
     * ถ้าข้อไหนแดง แปลว่ามีคนเปลี่ยนไปเทียบแบบ substring/fuzzy = ต้องรีบถอย
     *
     * @test
     */
    public function ขยายคำลงท้ายแล้วต้องไม่กินเคสห้ามติด(): void
    {
        // 💰 ขอซื้อ/ยกเลิก — ต้องตกเพื่อให้ด่าน escape ทำงาน (ไม่งั้นกินยอดขาย 39/99)
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'จันทร์ ขอดูดวงค่ะ'));
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'พุธ ยกเลิกค่ะ'));
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'จันทร์ ไม่เอาแล้ว'));
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'ศุกร์ จ่ายแล้วค่ะ'));

        // 🏷️ "จันทรา" = ชื่อแม่หมอเอง — เคสห้ามติดอันดับ 1
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'แม่หมอจันทราพยากรณ์'));
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'จันทราค่ะ'));
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'อาทิตยา'));

        // 🌌 ดาว/สถานที่/บทสวด
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'ดาวพุธ'));
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'จันทบุรี'));
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'จันทร์เจ้าขา'));
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'เสาไฟหน้าบ้าน'));

        // 🗓️ ประโยคที่มีชื่อวันปนแต่เจตนาอื่น
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'วันพุธนี้จะไปหาหมอค่ะ'));
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'เสาร์ไปงานแต่ง'));
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'พุธ อังคาร'), 'สองวัน = กำกวม');
    }

    /**
     * 🇹🇭 (2026-08-27) คำนำหน้า "วัน" สะกดตกหล่น + ช่วงเวลาพ่วงท้าย ต้องยังอ่านออก
     *
     * เคสจริง prod 27 ส.ค. (วัดจาก laravel.log 3.5 ชม. หลัง deploy 14:32 —
     * AI chat 25 ครั้ง มี 2 ครั้งเป็นคนขอดวงรายวันที่ตกร่อง = 8%):
     *   · แม่ฝน คำแจ่ม (PSID 28058628077130184, 18:06) พิมพ์ "วัพฤหัสบดีค่ะ" — ขาด "น" ตัวเดียว
     *   · PSID 26646988441640086 พิมพ์ "วันพุธกลางคืน" — ตระกูลเดียวกับ "พุธก่างคืนน่ะ"
     *     ที่บันทึกไว้ตั้งแต่ 2026-08-22 แต่ยังไม่เคยแก้
     *
     * ทั้งคู่ตกไป AI chat ซึ่ง **มโนคำทำนายสั้น ๆ ตอบแทน** ⇒ ลูกค้าได้คำตอบ "อะไรสักอย่าง"
     * ทุกครั้ง เลยไม่มีใครเห็นว่าเลนรายวันพลาด — นี่คือเหตุผลที่ต้องล็อกด้วยเทสต์
     *
     * @test
     */
    public function คำนำหน้าวันตกหล่นและช่วงเวลาพ่วงท้ายต้องยังอ่านออก(): void
    {
        // เคสจริงจาก prod 2026-08-27
        $this->assertSame(4, $this->invokeHidden('resolveBirthDayNameIndex', 'วัพฤหัสบดีค่ะ'), 'ขาด น ใน "วัน"');

        // 🌙 (2026-09-05) **เปลี่ยนค่าคาดหวังจาก 3 → 7 โดยตั้งใจ**
        //    รอบ 2026-08-27 แก้ให้ "วันพุธกลางคืน" เข้าเลนรายวันได้ (เดิมตกไป AI chat)
        //    แต่วิธีแก้คือ **ปอก "กลางคืน" ทิ้ง** ⇒ เข้าเลนได้จริง แต่ได้ดวงพุธกลางวัน
        //    ทั้งที่ตำราไทยใช้ **ราหู** เป็นดาวเจ้าเรือนของพุธกลางคืน = คนละดวงกันเลย
        //    ตอนนี้แปลงเป็นดัชนีวันเกิดที่ 8 (FortuneChartService::WEDNESDAY_NIGHT)
        $this->assertSame(7, $this->invokeHidden('resolveBirthDayNameIndex', 'วันพุธกลางคืน'), 'พุธกลางคืน = ราหู');
        $this->assertSame(7, $this->invokeHidden('resolveBirthDayNameIndex', 'พุธกลางคืนน่ะ'), 'ช่วงเวลา + คำลงท้าย');
        $this->assertSame(7, $this->invokeHidden('resolveBirthDayNameIndex', 'เกิดวันพุธตอนกลางคืนค่ะ'));
        $this->assertSame(7, $this->invokeHidden('resolveBirthDayNameIndex', 'พุธ ตอนดึกค่ะ'));

        // พุธกลางวัน (และคำที่ไม่ได้บอกกลางคืน) ต้องยังเป็น 3 เหมือนเดิม
        $this->assertSame(3, $this->invokeHidden('resolveBirthDayNameIndex', 'วันพุธ'));
        $this->assertSame(3, $this->invokeHidden('resolveBirthDayNameIndex', 'พุธค่ะ'));
        $this->assertSame(3, $this->invokeHidden('resolveBirthDayNameIndex', 'วันพุธกลางวัน'));
        $this->assertSame(3, $this->invokeHidden('resolveBirthDayNameIndex', 'พุธตอนเช้าค่ะ'));
        $this->assertSame(3, $this->invokeHidden('resolveBirthDayNameIndex', 'พุธตอนเย็นค่ะ'), 'เย็นก่อนย่ำค่ำ = ยังกลางวัน');

        // ⚠️ วันอื่นไม่มีการแยกกลางวัน/กลางคืนในตำรา — ห้ามเผลอแปลงข้าม
        $this->assertSame(6, $this->invokeHidden('resolveBirthDayNameIndex', 'เสาร์กลางคืนค่ะ'));
        $this->assertSame(1, $this->invokeHidden('resolveBirthDayNameIndex', 'จันทร์กลางคืนค่ะ'));

        // detectThaiDayName (จับกลางประโยค) ต้องเห็นตรงกัน — ใช้ตัวแปลงตัวเดียวกัน
        $this->assertSame(7, $this->invokeHidden('detectThaiDayName', 'เกิดวันพุธกลางคืนค่ะ'));
        $this->assertSame(3, $this->invokeHidden('detectThaiDayName', 'เกิดวันพุธค่ะ'));
    }

    /**
     * 🌙 (2026-09-05) ตัวแยก "กลางวัน/กลางคืน" ทางโหร
     *
     * ขอบเขตคือ **ย่ำค่ำ 18:00 → ย่ำรุ่ง 06:00** ของเช้าวันถัดไป (วันโหรเปลี่ยนตอนย่ำรุ่ง
     * ไม่ใช่เที่ยงคืนแบบปฏิทิน) ⇒ "เย็น" ยังเป็นกลางวัน แต่ "ค่ำ/ดึก/ตี 3/เช้ามืด" เป็นกลางคืน
     *
     * ⚠️ "เช้ามืด" มีคำว่า "เช้า" อยู่ในตัว — ทุกจุดต้องเช็คกลางคืน **ก่อน** กลางวันเสมอ
     *
     * @test
     */
    public function ตัวแยกกลางวันกลางคืนต้องตรงตามวันโหร(): void
    {
        foreach (['กลางคืน', 'ตอนดึก', 'ดึกมาก', 'ค่ำ ๆ', 'หัวค่ำ', 'เช้ามืด', 'ตี 3', 'ตี5'] as $night) {
            $this->assertTrue($this->invokeHidden('saysNightTime', $night), "'{$night}' ต้องเป็นกลางคืน");
        }

        foreach (['กลางวัน', 'ตอนเช้า', 'สาย ๆ', 'เที่ยง', 'บ่าย', 'เย็น'] as $day) {
            $this->assertFalse($this->invokeHidden('saysNightTime', $day), "'{$day}' ต้องไม่ใช่กลางคืน");
            $this->assertTrue($this->invokeHidden('saysDayTime', $day), "'{$day}' ต้องเป็นกลางวัน");
        }

        // ⚠️ "ค่ะ" (ค+่+ะ) ห้ามชนกับ "ค่ำ" (ค+่+ำ) — ต่างกันแค่สระตัวเดียว
        $this->assertFalse($this->invokeHidden('saysNightTime', 'วันพุธค่ะ'));
        $this->assertFalse($this->invokeHidden('saysNightTime', 'พุธครับ'));

        // ตัวแปลงต้องแตะเฉพาะวันพุธ
        $this->assertSame(7, $this->invokeHidden('applyWednesdayHalf', 3, 'พุธกลางคืน'));
        $this->assertSame(3, $this->invokeHidden('applyWednesdayHalf', 3, 'พุธกลางวัน'));
        $this->assertSame(3, $this->invokeHidden('applyWednesdayHalf', 3, 'พุธ'));
        $this->assertSame(6, $this->invokeHidden('applyWednesdayHalf', 6, 'เสาร์กลางคืน'));
        $this->assertSame(0, $this->invokeHidden('applyWednesdayHalf', 0, 'อาทิตย์ตอนดึก'));

        // ตระกูลเดียวกันที่ต้องรอดไปด้วย
        $this->assertSame(5, $this->invokeHidden('resolveBirthDayNameIndex', 'ศุกร์ตอนเช้าค่ะ'));
        $this->assertSame(1, $this->invokeHidden('resolveBirthDayNameIndex', 'วัจันทร์ค่ะ'));
        $this->assertSame(2, $this->invokeHidden('resolveBirthDayNameIndex', 'วนอังคารค่ะ'));

        // looksLikeStandaloneDayName ต้องเห็นตรงกัน (ใช้ตัวปอกชุดเดียวกัน)
        $this->assertTrue($this->invokeHidden('looksLikeStandaloneDayName', 'วัพฤหัสบดีค่ะ'));
        $this->assertTrue($this->invokeHidden('looksLikeStandaloneDayName', 'วันพุธกลางคืน'));

        // 🛡️ เปิดกว้างที่คำนำหน้า/ช่วงเวลาแล้ว ด่านห้ามติดต้องไม่อ่อนลง
        //    (ซ้ำกับเทสต์ด้านบนโดยตั้งใจ — ถ้าใครแก้ regex ปอกให้กว้างขึ้น ต้องแดงที่นี่ก่อน)
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'เสาร์ไปงานแต่ง'), 'ท้ายไม่ใช่ช่วงเวลา');
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'เสาไฟหน้าบ้าน'));
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'วันพุธนี้จะไปหาหมอค่ะ'));
        $this->assertNull($this->invokeHidden('resolveBirthDayNameIndex', 'วาสนาดีจัง'), '"วา" ไม่ใช่คำนำหน้าวัน');
    }

    /**
     * 🌙 (2026-08-27) ประตูที่ 4 ของด่าน 2 พึ่ง looksLikeShortYes เป็นตัวคุมความแคบ
     *
     * เคสจริง แม่ฝน คำแจ่ม (PSID 28058628077130184, 18:04) พิมพ์ **"ดูค่ะ"**
     * ทั้งที่ birth_day=4 อยู่ใน DB ตั้งแต่ 15 ส.ค. → ด่าน 2 ตีตก → AI chat เสนอขาย
     *
     * ⚠️ ถ้าใครเผลอเติมคำที่มี "ดูดวง" ลง DAILY_SHORT_YES เทสต์นี้จะแดง —
     *    ตั้งใจ เพราะ "ดูดวงค่ะ" ต้องไหลไป flow ขาย ไม่ใช่ได้ของฟรี
     *
     * @test
     */
    public function ตอบรับสั้นๆต้องแคบพอไม่กินคำขอดูดวงแบบเสียเงิน(): void
    {
        $this->assertTrue($this->invokeHidden('looksLikeShortYes', 'ดูค่ะ'), 'เคสจริง แม่ฝน คำแจ่ม');
        $this->assertTrue($this->invokeHidden('looksLikeShortYes', 'เอาเลยค่ะ'));
        $this->assertTrue($this->invokeHidden('looksLikeShortYes', 'ขอค่ะ'));
        $this->assertTrue($this->invokeHidden('looksLikeShortYes', 'โอเค'));

        // ❌ ห้ามติด — พวกนี้ต้องไปเข้า flow ขาย/คุยปกติ
        $this->assertFalse($this->invokeHidden('looksLikeShortYes', 'ดูดวงค่ะ'), 'ต้องไป flow ขาย');
        $this->assertFalse($this->invokeHidden('looksLikeShortYes', 'ราคาเท่าไหร่'));
        $this->assertFalse($this->invokeHidden('looksLikeShortYes', 'สวัสดีค่ะ'));
        $this->assertFalse($this->invokeHidden('looksLikeShortYes', ''));
    }
}
