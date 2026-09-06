<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\FortuneScopeGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * 📿 ด่านตัด "ตัวบทสวด/คาถา" ต้องไม่กินข้อความปกติของแม่หมอ
 *
 * 🚨 ทำไมต้องมีเทสต์นี้ (2026-09-06 — บิล FTU-260906-V4421):
 *   ลูกค้ากดซื้อ Deep 39 แล้วกำลังจะโอน → `SendBillReminderJob` ยิงข้อความเตือนโอนเงิน
 *   → ด่านนี้ตัดทิ้ง **ทั้งใบ** แล้วส่ง "(บทสวดเต็ม ๆ แม่หมอไม่ท่อง...)" ไปแทน
 *   ลูกค้าไม่เคยถามเรื่องบทสวดสักคำ
 *
 *   ต้นเหตุ: `paliRunOffset()` นับ "ก้อนที่คั่นด้วยช่องว่าง" — แต่ภาษาไทยไม่เว้นวรรค
 *   ระหว่างคำ ⇒ 1 ก้อน = 1 วลี และวลีสุภาพลงท้าย `นะคะ` / `เลยค่ะ` / `ให้ค่ะ`
 *   ซึ่งลงท้ายด้วย `ะ` ทุกตัว ⇒ ครบ 4 วลี = เด้ง แล้วจุดตัดอยู่ที่ 0 ⇒ หายทั้งข้อความ
 *
 *   ข้อความพวกนี้คือ "ตอนที่ลูกค้ากำลังจะจ่ายเงิน" ⇒ ปล่อยให้ regress ไม่ได้
 *
 * ⚠️ ใช้ PHPUnit\Framework\TestCase ตรง ๆ (ไม่แตะ DB) เครื่อง dev ที่ไม่มี MySQL รันได้
 */
class FortuneChantStripTest extends TestCase
{
    /**
     * ประโยคจริงของแม่หมอ/ลูกค้า — ห้ามโดนแตะแม้แต่ตัวเดียว
     *
     * @return array<string, array{0: string}>
     */
    public static function innocentMessages(): array
    {
        return [
            // ⬇️ เคสต้นเรื่อง — reproduce จาก prod ได้เป๊ะ (before_len 115 → after_len 135)
            'เตือนโอนเงิน (FTU-260906-V4421)' => ['โอนแล้วส่งสลิปมาได้เลยนะคะ แม่หมอจะเช็คให้ค่ะ เดี๋ยวเริ่มทำนายให้เลยค่ะ รอสักครู่นะคะ'],
            'แจ้งยอด' => ['ยอดนี้ ฿39.21 นะคะ โอนตรงยอดเลยนะคะ ระบบจะตัดบิลอัตโนมัติค่ะ แล้วแม่หมอจะเริ่มดูให้ทันทีค่ะ'],
            'รอสลิป' => ['แม่หมอเห็นบิลแล้วนะคะ รอโอนอยู่ค่ะ พอเงินเข้าตรงยอด แม่หมอจะเปิดไพ่ให้ทันทีเลยนะคะ ใจเย็น ๆ ค่ะ'],
            'แอดมินพิมพ์เอง' => ['แม่หมอ เห็นแล้วนะคะ รอ โอน ถ้าตรงยอด จะเริ่มทำนายให้เลยค่ะ'],
            // ลูกค้าคุณไสย — เคยโดนกลืนมาแล้วรอบก่อน ห้ามซ้ำ
            'ลูกค้าโดนของ' => ['หนูโดนของ มีคนลงของใส่หนู อยากรู้ว่าจะแก้ยังไง ช่วยหนูหน่อยนะคะ'],
            // พูดถึงการสวดมนต์ได้ ตราบใดที่ไม่ใช่ "ตัวบท"
            'แนะนำให้สวดมนต์ (ไม่มีตัวบท)' => ['ทำบุญ ใส่บาตร แผ่เมตตา สวดมนต์ก่อนนอน แล้วดวงจะดีขึ้นนะคะ'],
            'ชวนดูดวง' => ['ทักมาเลยนะคะ แม่หมอเปิดไพ่ให้ฟรีหนึ่งใบค่ะ อยากรู้เรื่องอะไรบอกมาได้เลยค่ะ รอฟังอยู่นะคะ'],
        ];
    }

    #[DataProvider('innocentMessages')]
    public function test_ข้อความปกติต้องไม่ถูกตัด(string $message): void
    {
        $result = FortuneScopeGuard::stripChantText($message);

        $this->assertFalse(
            $result['stripped'],
            "ด่านบทสวดไปกินข้อความปกติ: {$message}"
        );
        $this->assertSame($message, $result['text']);
    }

    /**
     * ตัวบทจริงยังต้องโดนตัด — ทั้งเลนคำเปิดบทที่รู้จัก และเลนความหนาแน่น
     */
    public function test_ตัวบทที่รู้จักยังต้องถูกตัด(): void
    {
        $result = FortuneScopeGuard::stripChantText(
            'นะโม ตัสสะ ภะคะวะโต อะระหะโต สัมมาสัมพุทธัสสะ'
        );

        $this->assertTrue($result['stripped']);
        $this->assertStringNotContainsString('ภะคะวะโต', $result['text']);
    }

    public function test_ตัวบทท้ายประโยคถูกตัดแต่หัวข้อยังอยู่(): void
    {
        $result = FortuneScopeGuard::stripChantText(
            'ทำเองที่บ้านก่อนนะลูก — นะโม ตัสสะ ภะคะวะโต อะระหะโต สัมมาสัมพุทธัสสะ'
        );

        $this->assertTrue($result['stripped']);
        $this->assertStringContainsString('ทำเองที่บ้านก่อนนะลูก', $result['text']);
        $this->assertStringNotContainsString('ภะคะวะโต', $result['text']);
    }

    public function test_เลนความหนาแน่นยังจับตัวบทที่ไม่อยู่ในลิสต์ได้(): void
    {
        $result = FortuneScopeGuard::stripChantText(
            "แม่หมอแนะนำให้สวดก่อนนอนนะลูก\nยันตัง สันตัง วะรัง ปะณีตัง"
        );

        $this->assertTrue($result['stripped']);
        $this->assertStringContainsString('แม่หมอแนะนำให้สวดก่อนนอนนะลูก', $result['text']);
        $this->assertStringNotContainsString('ปะณีตัง', $result['text']);
    }

    /**
     * 🔒 invariant สำคัญที่สุด — **ห้ามส่งหมายเหตุลอย ๆ จากการเดา**
     *
     * ถ้าฮิวริสติกความหนาแน่นกินข้อความจนไม่เหลืออะไร และไม่มีคำเปิดบทที่รู้จักแน่
     * ⇒ ถือว่าจับผิด คืนของเดิม (ยอมปล่อยหลุด ดีกว่าให้คนจ่ายเงินได้ข้อความไม่รู้เรื่อง)
     */
    public function test_ตัดจนหมดโดยไม่มีคำเปิดบทที่รู้จัก_ต้องคืนของเดิม(): void
    {
        $onlyGuessed = 'ยันตัง สันตัง วะรัง ปะณีตัง';

        $result = FortuneScopeGuard::stripChantText($onlyGuessed);

        $this->assertFalse($result['stripped']);
        $this->assertSame($onlyGuessed, $result['text']);
    }

    public function test_removed_ต้องบอกได้ว่าไปกินอะไรมา(): void
    {
        $result = FortuneScopeGuard::stripChantText(
            'ทำเองที่บ้านก่อนนะลูก — นะโม ตัสสะ ภะคะวะโต อะระหะโต สัมมาสัมพุทธัสสะ'
        );

        $this->assertTrue($result['stripped']);
        $this->assertStringContainsString('นะโม ตัสสะ', $result['removed']);
    }
}
