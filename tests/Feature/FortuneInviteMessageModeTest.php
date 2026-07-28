<?php

namespace Tests\Feature;

use App\Models\FortuneInviteMessage;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneBotMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 💬 ชุดข้อความ DM แยกตามโหมด + placeholder ลิงก์รายคน
 *
 * ล็อกกติกาที่พลาดแล้วเสียงสวนกันเอง:
 *  1. โหมด transfer ต้องหยิบชุด transfer ก่อนเสมอ (ชุดเดิมชวน "ทักมาดูดวงในแชท")
 *  2. โหมด classic ต้องไม่หยิบชุด transfer มาใช้ (ลูกค้าจะได้ลิงก์ไปเว็บที่ยังไม่เปิด)
 *  3. ไม่มีชุด transfer เลย → ต้องตกไปใช้ชุดกลาง ห้ามคืน null (DM เงียบ = เสียลูกค้า)
 *  4. {{web_link}} ต้องกลายเป็น magic link รายคน — ไม่ใช่ค่าคงที่ใน DB
 *  5. สร้างลิงก์ไม่ได้ → ตัดทั้งบรรทัดทิ้ง ห้ามเหลือ "กดที่นี่ 👉" ลอย ๆ
 */
class FortuneInviteMessageModeTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '61550000000777';

    private function settings(array $override = []): FortuneTellingSetting
    {
        FortuneTellingSetting::clearSettingsCache();

        FortuneTellingSetting::getSettings()->forceFill(array_merge([
            'fortune_bot_mode' => FortuneBotMode::MODE_TRANSFER,
            'line_bot_basic_id' => '@maemor',
        ], $override))->save();

        FortuneTellingSetting::clearSettingsCache();

        return FortuneTellingSetting::getSettings();
    }

    private function makeMessage(string $mode, string $message = 'ข้อความทดสอบ'): FortuneInviteMessage
    {
        return FortuneInviteMessage::create([
            'message' => $message,
            'category' => 'test',
            'mode' => $mode,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_โหมด_transfer_หยิบชุด_transfer_ก่อน(): void
    {
        $this->settings();

        $this->makeMessage(FortuneInviteMessage::MODE_ALL, 'ชุดกลาง');
        $this->makeMessage(FortuneInviteMessage::MODE_TRANSFER, 'ชุด transfer');

        for ($i = 0; $i < 5; $i++) {
            $this->assertSame('ชุด transfer', FortuneInviteMessage::pickActive()?->message);
        }
    }

    public function test_ไม่มีชุด_transfer_ต้องตกไปใช้ชุดกลาง_ไม่เงียบ(): void
    {
        $this->settings();

        $this->makeMessage(FortuneInviteMessage::MODE_ALL, 'ชุดกลาง');

        $this->assertSame('ชุดกลาง', FortuneInviteMessage::pickActive()?->message);
    }

    public function test_โหมด_classic_ต้องไม่หยิบชุด_transfer(): void
    {
        $this->settings(['fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC]);

        $this->makeMessage(FortuneInviteMessage::MODE_ALL, 'ชุดกลาง');
        $this->makeMessage(FortuneInviteMessage::MODE_TRANSFER, 'ชุด transfer');

        for ($i = 0; $i < 5; $i++) {
            $this->assertSame('ชุดกลาง', FortuneInviteMessage::pickActive()?->message);
        }
    }

    public function test_แทน_web_link_ด้วย_magic_link_รายคน(): void
    {
        $this->settings(['enable_web_fortune_button' => true]);

        $msg = $this->makeMessage(
            FortuneInviteMessage::MODE_TRANSFER,
            "ดูดวงฟรีเลย{name}\n👉 {{web_link}}"
        );

        $a = $msg->render('สมชาย', self::PSID, 'facebook');
        $b = $msg->render('สมชาย', '61550000000888', 'facebook');

        $this->assertStringNotContainsString('{{web_link}}', $a);
        $this->assertStringContainsString('/web-fortune/go?tk=', $a);
        $this->assertNotSame($a, $b, 'magic link ต้องผูกรายคน ไม่ใช่ลิงก์เดียวใช้ร่วมกัน');
    }

    public function test_แทน_line_link_ด้วยลิงก์แอดเพื่อน(): void
    {
        $this->settings();

        $msg = $this->makeMessage(FortuneInviteMessage::MODE_TRANSFER, 'แอดไลน์: {{line_link}}');

        $this->assertStringContainsString('https://line.me/R/ti/p/@maemor', $msg->render('สมชาย', self::PSID));
    }

    public function test_สร้างลิงก์ไม่ได้_ต้องตัดทั้งบรรทัดทิ้ง(): void
    {
        // ปุ่มเว็บปิด + ไม่ได้ตั้ง LINE OA = สร้างลิงก์ไม่ได้ทั้งคู่
        $this->settings(['enable_web_fortune_button' => false, 'line_bot_basic_id' => null]);

        $msg = $this->makeMessage(
            FortuneInviteMessage::MODE_TRANSFER,
            "🌙 แม่หมอเปิดไพ่ให้ฟรีนะคะ\n👉 {{web_link}}\n💚 หรือทาง LINE: {{line_link}}"
        );

        $rendered = $msg->render('สมชาย', self::PSID, 'facebook');

        $this->assertStringNotContainsString('{{web_link}}', $rendered);
        $this->assertStringNotContainsString('{{line_link}}', $rendered);
        $this->assertStringNotContainsString('👉', $rendered, 'บรรทัดที่ไม่มีลิงก์ต้องถูกตัดทิ้งทั้งบรรทัด');
        $this->assertStringNotContainsString('💚', $rendered);
        $this->assertStringContainsString('แม่หมอเปิดไพ่ให้ฟรี', $rendered, 'เนื้อหาหลักต้องยังอยู่');
    }

    /**
     * 🎁 ปุ่ม "ดูฟรีที่เว็บ" ใน DM ชวน — ต้องขึ้นเฉพาะตอนเปิดสวิตช์ปุ่มเว็บ
     *    ปุ่มที่กดแล้วไม่มีอะไรเกิดขึ้น แย่กว่าไม่มีปุ่ม
     */
    public function test_ปุ่มดูฟรีที่เว็บขึ้นเฉพาะตอนเปิดสวิตช์(): void
    {
        // ปิดสวิตช์ = ปุ่มเดิม 3 ตัวเป๊ะ (พฤติกรรมเดิม 100%)
        $this->settings(['enable_web_fortune_button' => false]);

        $off = FortuneInviteMessage::quickReplies();
        $this->assertCount(3, $off);
        $this->assertSame(
            ['INVITE_READ_NOW', 'INVITE_SNOOZE_7D', 'INVITE_OPTOUT'],
            array_column($off, 'payload')
        );

        // เปิดสวิตช์ = เพิ่มปุ่มเว็บ **เป็นตัวแรก** (quick reply บนมือถือเลื่อนแนวนอน
        // ตัวท้ายมักตกขอบจอ — ปุ่มที่อยากให้คนกดต้องอยู่หน้าสุด)
        $this->settings(['enable_web_fortune_button' => true]);

        $on = FortuneInviteMessage::quickReplies();
        $this->assertCount(4, $on);
        $this->assertSame('INVITE_FREE_WEB', $on[0]['payload']);
        $this->assertSame('🎁 ดูฟรีที่เว็บ', $on[0]['title']);

        // ปุ่มเดิมต้องอยู่ครบ ห้ามหายไปตัวไหน (โดยเฉพาะ opt-out)
        $this->assertSame(
            ['INVITE_READ_NOW', 'INVITE_SNOOZE_7D', 'INVITE_OPTOUT'],
            array_values(array_slice(array_column($on, 'payload'), 1))
        );

        // ทุก label ต้องไม่เกินลิมิต 20 ตัวอักษรของ Facebook
        foreach ($on as $qr) {
            $this->assertLessThanOrEqual(20, mb_strlen($qr['title']), "label ยาวเกิน: {$qr['title']}");
        }
    }

    public function test_ข้อความเดิมที่ไม่มี_placeholder_ต้องไม่เปลี่ยนพฤติกรรม(): void
    {
        $this->settings(['fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC]);

        $msg = $this->makeMessage(FortuneInviteMessage::MODE_ALL, 'ช่วงนี้ดวงกำลังเปลี่ยนนะคะคุณ{name}');

        $this->assertSame('ช่วงนี้ดวงกำลังเปลี่ยนนะคะคุณสมชาย', $msg->render('สมชาย'));
    }
}
