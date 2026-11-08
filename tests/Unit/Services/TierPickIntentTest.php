<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\TierPickIntent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 🛒 ล็อกด่าน "ประโยคนี้คือการเลือกแพคเกจ หรือแค่มีคำของแพคเกจปนอยู่"
 *
 * 🚨 ทำไมต้องมี (เจ้าของ 2026-11-09: "ทำไมดูฟรีเสร็จเปิดบิล 39 ให้เอง เฉยเลย")
 *   handleTierChoice จับ '39' / 'ละเอียด' แบบ substring แล้วออกบิลทันที — เคสจริงบน FB:
 *     #14558 "…เดี๋ยวให้หนูกลับบ้านแล้วหนูจะดูให้ลูกทำให้ 39 บาทใช่ไหมคะเรื่องละ"
 *     #14605 "ถ้าดูเกี่ยวกับลายละเอียดครูหมอ ต้องโปรไหนคะ"
 *     #14660 "ผมจะดูกับแม่ครับแบบละเอียดและให้แมช่วยขอเวลาอีกไม่นานครับแม่"
 *   ทั้งสามได้บิล 39 ที่ไม่ได้สั่ง ไม่มีใบไหนจ่าย
 *
 * ⚠️ ฝั่งกลับกันสำคัญเท่ากัน: คนที่เลือกจริงต้องได้บิลทันทีเหมือนเดิม
 *    (เจ้าของเคยติ: "ไม่ใช่มาถาม แล้วถามอีก")
 *
 * PHPUnit\Framework\TestCase ตรง ๆ — คลาสนี้ไม่แตะ DB/Cache
 */
class TierPickIntentTest extends TestCase
{
    /** ข้อความจริงจาก prod + รูปเดียวกันที่ต้องไม่ออกบิล */
    public static function notAPickProvider(): array
    {
        return [
            'prod #14558 ถามราคา + ผัดกลับบ้าน' => ['คนสองคนขอบคุณค่ะหนูอยู่ต่างจังหวัดหนูทำส่งไม่เป็นเดี๋ยวให้หนูกลับบ้านแล้วหนูจะดูให้ลูกทำให้ 39 บาทใช่ไหมคะเรื่องละ'],
            'prod #14605 ถามว่าต้องโปรไหน' => ['ถ้าดูเกี่ยวกับลายละเอียดครูหมอ ต้องโปรไหนคะ'],
            'prod #14660 ขอเวลา' => ['ผมจะดูกับแม่ครับแบบละเอียดและให้แมช่วยขอเวลาอีกไม่นานครับแม่'],
            'prod #14660 ก่อนหน้า ไว้คราวหน้า' => ['แม่ครัว าใว้คราวหน้าก่อนใด้มั้ยครับผมยังไม่ใด้มีบัญชีในแอปครับยังไม่สดวกผมต้องข โทษด้วยครับ'],
            'prod #14660 ยังไม่สดวก (สะกดตามจริง)' => ['ผมหลายเรื่องมากครับแต่ผมยังไม่สดวกตอนนี้ครับแม่'],
            'ถามเลข 39 คือเท่าไหร่' => ['39 คือได้อะไรบ้างคะ'],
            'ถามเทียบ 39 หรือ 99' => ['39 กับ 99 ต่างกันยังไงคะ'],
            'ถามด้วย ?' => ['แบบละเอียด 39 ?'],
            'ราคาแพงไป' => ['39 แพงไปค่ะ'],
            'ไม่มีเงิน' => ['อยากดูแบบละเอียดแต่ไม่มีเงินค่ะ'],
            'มีเงินในบัญชีก่อน' => ['เดี๋ยวลูกมีเงินในบัญชีก่อนค่อยดู 39 นะคะ'],
            'ไว้พรุ่งนี้' => ['ไว้พรุ่งนี้ค่อยดู 39 นะคะ'],
            'ไม่เอา' => ['ไม่เอา 99 ค่ะ'],
            'คุณไสยมีจริงไหม' => ['คุณไสยมีจริงไหมคะ'],
            'เอาไว้ก่อน (ขึ้นต้นคำรับ)' => ['เอาไว้ก่อนนะคะ'],
        ];
    }

    #[Test]
    #[DataProvider('notAPickProvider')]
    public function question_or_deferral_is_not_a_package_pick(string $text): void
    {
        $this->assertTrue(TierPickIntent::isNotAPick($text), "ต้องไม่ออกบิลจาก \"{$text}\"");
    }

    /** ลูกค้าเลือกจริง — ต้องผ่านด่าน (ออกบิลได้ทันที) */
    public static function realPickProvider(): array
    {
        return [
            'พิมพ์ราคาเดี่ยว' => ['39'],
            'ราคา + บาท' => ['39 บาท'],
            'เอา 39 ค่ะ' => ['เอา 39 ค่ะ'],
            'ขอดูแบบละเอียดค่ะ' => ['ขอดูแบบละเอียดค่ะ'],
            'ขอ 39 ได้ไหมคะ (ขอแบบสุภาพ)' => ['ขอ 39 ได้ไหมคะ'],
            'ดู 99 ได้มั้ยคะ (ขอแบบสุภาพ)' => ['ดู 99 ได้มั้ยคะ'],
            'ป้ายปุ่ม 39' => ['🔹 ดูดวง 39'],
            'ป้ายปุ่ม vip 99' => ['ดู vip ส่วนตัว 99บาท'],
            'ป้ายปุ่มเชิงลึก' => ['🌟 ดูดวงเชิงลึก'],
            'แบบปกติก็ได้' => ['ดูแบบปกติก็ได้ค่ะ'],
            'เลือก + เล่าเรื่อง' => ['เอาแบบ 39 ค่ะ อยากดูเรื่องความรัก'],
            'เลือก + พรุ่งนี้ในเนื้อเรื่อง' => ['ขอ 39 ดูเรื่องสัมภาษณ์งานพรุ่งนี้ค่ะ'],
            'เลือก + ยังไม่มีแฟน' => ['ยังไม่มีแฟนค่ะ ขอดู 39'],
            'เลือก + เกี่ยวกับ (มี กี่ ซ่อน)' => ['ขอดู 39 เกี่ยวกับความรักค่ะ'],
            'เลือก + สมัยนี้ (มี มัย ซ่อน)' => ['เอาแบบละเอียดค่ะ สมัยนี้งานหายาก'],
            'ส่วนตัว + ขอเลขธนาคาร (Wanpen)' => ['ส่วนตัวค่ะชื่อธนาคารด้วยค่ะ'],
        ];
    }

    #[Test]
    #[DataProvider('realPickProvider')]
    public function a_real_pick_still_passes(string $text): void
    {
        $this->assertFalse(TierPickIntent::isNotAPick($text), "\"{$text}\" คือการเลือกแพคเกจ ต้องออกบิลได้");
    }

    #[Test]
    public function price_must_be_a_standalone_number(): void
    {
        $this->assertTrue(TierPickIntent::mentionsPrice('39', 39));
        $this->assertTrue(TierPickIntent::mentionsPrice('เอา 39บาท', 39));
        $this->assertTrue(TierPickIntent::mentionsPrice('ดูดวง39', 39));
        $this->assertTrue(TierPickIntent::mentionsPrice('99฿', 99));

        // เลขที่เป็นส่วนหนึ่งของเลขอื่น ไม่ใช่ราคา
        $this->assertFalse(TierPickIntent::mentionsPrice('เกิด 12/3/2539', 39));
        $this->assertFalse(TierPickIntent::mentionsPrice('เบอร์ 0839123456', 39));
        $this->assertFalse(TierPickIntent::mentionsPrice('เกิดปี 1999', 99));
        $this->assertFalse(TierPickIntent::mentionsPrice('อะไรก็ได้', 0));
    }

    #[Test]
    public function compound_words_that_hide_a_package_word_are_masked(): void
    {
        // 'รายละเอียด' = ขอข้อมูล · 'ผิดปกติ' = เล่าอาการ — ไม่ใช่ "แบบละเอียด" / "แบบปกติ"
        $this->assertStringNotContainsString('ละเอียด', TierPickIntent::keywordText('ขอรายละเอียดหน่อยค่ะ'));
        $this->assertStringNotContainsString('ละเอียด', TierPickIntent::keywordText('ลายละเอียดครูหมอ'));
        $this->assertStringNotContainsString('ปกติ', TierPickIntent::keywordText('ช่วงนี้ร่างกายผิดปกติค่ะ'));

        // คำเลือกจริงต้องยังอยู่
        $this->assertStringContainsString('ละเอียด', TierPickIntent::keywordText('ขอแบบละเอียดค่ะ'));
        $this->assertStringContainsString('ปกติ', TierPickIntent::keywordText('ดูแบบปกติก็ได้ค่ะ'));
    }
}
