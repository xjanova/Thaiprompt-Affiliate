<?php

namespace Tests\Feature;

use App\Models\FortuneInviteMessage;
use App\Services\Fortune\WebHandoffCopy;
use Database\Seeders\FortuneWebInviteCampaignSeeder;
use Database\Seeders\FortuneWebOnlyInviteMessageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 🌙 (2026-11-14 เจ้าของสั่ง) ปุ่ม "เข้าห้องแม่หมอ" → "เข้าห้องดูดวง" ในแชทบอท Facebook
 *
 * ล็อก:
 *  1. ป้ายปุ่มและข้อความที่ลูกค้าเห็นไม่มีคำเดิมแล้ว
 *  2. migration แก้ข้อความชวน (mode=web) แถวเดิมใน DB — แถวโหมดอื่นไม่ถูกแตะ · down() คืนได้
 *  3. seeder รันซ้ำหลัง migration ไม่สร้างแถวซ้ำ (จับคู่ด้วยตัวข้อความ)
 */
class WebRoomLabelRenameTest extends TestCase
{
    use RefreshDatabase;

    private const OLD = 'เข้าห้องแม่หมอ';

    private function migration(): object
    {
        return require database_path('migrations/2026_11_14_000002_rename_web_room_label_in_invite_messages.php');
    }

    public function test_ป้ายปุ่มและข้อความในแชทใช้คำว่าเข้าห้องดูดวง(): void
    {
        $this->assertSame('🌙 เข้าห้องดูดวง', WebHandoffCopy::LABEL_WEB_ROOM);

        foreach (WebHandoffCopy::allLines() as $key => $line) {
            $this->assertStringNotContainsString(self::OLD, $line, "บรรทัด {$key} ยังใช้ชื่อปุ่มเดิม");
        }
        foreach (FortuneWebInviteCampaignSeeder::rows() as $row) {
            $this->assertStringNotContainsString(self::OLD, $row['message']);
        }
    }

    public function test_migration_แก้ข้อความชวนแถวเดิม_ไม่แตะโหมดอื่น_และ_seeder_ไม่สร้างซ้ำ(): void
    {
        // ตั้งต้นแบบ prod ก่อน deploy: ล้างแถวที่ migration seed เดิมสร้างไว้ด้วยถ้อยคำใหม่ (DB เทสรันโค้ดปัจจุบัน)
        \Illuminate\Support\Facades\DB::table('fortune_invite_messages')->delete();

        $old = '🎁 คุณ{name}คะ ไม่ต้องเตรียมอะไรเลย แค่แตะ 🌙 เข้าห้องแม่หมอ ก็ดูดวงฟรีได้ สมัครฟรี ฉลองเปิดเว็บใหม่ค่ะ';
        $web = FortuneInviteMessage::create([
            'message' => $old, 'category' => FortuneInviteMessage::CATEGORY_WEB_INVITE,
            'mode' => FortuneInviteMessage::MODE_WEB, 'is_active' => true, 'sort_order' => 1,
        ]);
        $trashed = FortuneInviteMessage::create([
            'message' => 'แตะทีเดียวก็เข้าห้องแม่หมอได้เลยค่ะ ไม่ต้องตั้งรหัส ไม่ต้องกรอกอะไรเลย 🌙', 'category' => 'web-room',
            'mode' => FortuneInviteMessage::MODE_WEB, 'is_active' => true, 'sort_order' => 2,
        ]);
        $trashed->delete();
        $classic = FortuneInviteMessage::create([
            'message' => 'แม่หมอรอที่ห้องแม่หมอค่ะ เข้าห้องแม่หมอ', 'category' => 'general',
            'mode' => FortuneInviteMessage::MODE_CLASSIC, 'is_active' => true, 'sort_order' => 3,
        ]);

        $this->migration()->up();

        $this->assertSame(str_replace(self::OLD, 'เข้าห้องดูดวง', $old), $web->fresh()->message);
        $this->assertStringNotContainsString(self::OLD, FortuneInviteMessage::withTrashed()->find($trashed->id)->message, 'แถวที่ลบแล้วต้องแก้ด้วย กัน seeder ฟื้นแถวใหม่');
        $this->assertSame('แม่หมอรอที่ห้องแม่หมอค่ะ เข้าห้องแม่หมอ', $classic->fresh()->message, 'โหมดอื่นห้ามแตะ');

        // seeder รันหลัง migration (ลำดับของ deploy) — แถวที่แก้แล้วตรงกับ seeder จึงไม่เกิดแถวซ้ำ
        $before = FortuneInviteMessage::withTrashed()->where('message', $web->fresh()->message)->count();
        FortuneWebInviteCampaignSeeder::seed();
        $this->assertSame($before, FortuneInviteMessage::withTrashed()->where('message', $web->fresh()->message)->count());
        (new FortuneWebOnlyInviteMessageSeeder)->run();
        $this->assertSame(1, FortuneInviteMessage::withTrashed()->where('message', WebHandoffCopy::invite(7))->count(), 'ข้อชวน 7 ต้องไม่ถูกสร้างซ้ำ');

        $this->migration()->down();
        $this->assertSame($old, $web->fresh()->message);
    }
}
