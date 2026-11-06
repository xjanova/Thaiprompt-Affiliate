<?php

namespace Database\Seeders;

use App\Models\FortuneInviteMessage;
use App\Services\Fortune\WebHandoffCopy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * 🌐 (2026-11-03) ข้อความชวนชุด FB web-only (mode='web') — พาไป "ห้องแม่หมอ" บนเว็บจันทรา
 *
 * เจ้าของสั่ง: Facebook แบนเพจที่มีสลิปในแชท → ห้ามดูดวง/จ่ายเงินใน Messenger อีก
 * DM ชวนต้อง "เนียน" ห้ามพูดเรื่องเงินเด็ดขาด (design หัวข้อ 5)
 *
 * ถ้อยคำมาจาก WebHandoffCopy::INVITES ที่เดียว (ห้ามพิมพ์ซ้ำในไฟล์นี้ — แก้ที่นั่นแล้ว seed ใหม่)
 *
 * กติกา:
 *   - ห้ามมีคำว่า ฟรี บาท ฿ ค่าครู โอน สลิป QR บัญชี LINE สายงาน รายได้ กระเป๋าเงิน (มีเทสต์ lint)
 *   - ห้ามวาง {{web_link}} / URL ดิบ — ลิงก์อยู่ในปุ่มที่ตอบหลังลูกค้ากด 🌙 เข้าห้องแม่หมอ
 *   - ไม่แตะชุดเดิม (all/classic/transfer/daily) — ปิดธงแล้วทุกอย่างกลับเป็นแบบเดิม
 *
 * ข้อที่ seed แบบปิดไว้ (is_active=false) ให้แอดมินเปิดเอง:
 *   - ข้อ 3 (เรื่องหัวใจ) — ใช้ได้เฉพาะลูกค้าที่ถามเรื่องความรัก DM ชวนแบบสุ่มไม่รู้หัวข้อ
 *   - ข้อ 8, 9 — ใช้ได้หลัง deploy ความต่อเนื่องของแชท (เว็บจำบทสนทนาจาก FB) แล้วเท่านั้น
 *
 * idempotent: จับคู่ด้วยตัวข้อความ (รวมแถวที่ถูกลบแบบ soft delete — แอดมินลบแล้วห้ามฟื้น)
 */
class FortuneWebOnlyInviteMessageSeeder extends Seeder
{
    /** ช่วงเวลา [hour_from, hour_to] ตามชนิดหน้าต่างของ WebHandoffCopy (เวลาไทย) */
    public const WINDOW_HOURS = [
        WebHandoffCopy::WINDOW_EVENING => [18, 22],
        WebHandoffCopy::WINDOW_MORNING => [5, 9],
    ];

    /**
     * สร้างข้อความชุด web
     */
    public function run(): void
    {
        $this->command?->info('🌱 กำลัง seed ข้อความชวนชุด FB web-only (mode=web)...');

        $created = self::seed();

        $this->command?->info($created > 0
            ? '✅ Seed ข้อความชุด web สำเร็จ ('.$created.' ข้อความ)'
            : '   มีข้อความชุด web ครบแล้ว ข้าม...');
    }

    /**
     * แถวที่จะ seed (ใช้ทั้ง seeder, migration และเทสต์ lint)
     *
     * @return array<int, array{number:int, message:string, category:string, is_active:bool, hour_from:?int, hour_to:?int}>
     */
    public static function rows(): array
    {
        $rows = [];

        foreach (WebHandoffCopy::INVITES as $n => $invite) {
            [$from, $to] = self::WINDOW_HOURS[$invite['window']] ?? [null, null];

            // ข้อเฉพาะหัวข้อ / ข้อที่ต้องรอ continuity → seed แบบปิดไว้
            $active = $invite['topic'] === null && ! $invite['continuity'];

            $rows[] = [
                'number' => (int) $n,
                'message' => $invite['text'],
                'category' => self::categoryFor($invite),
                'is_active' => $active,
                'hour_from' => $from,
                'hour_to' => $to,
            ];
        }

        return $rows;
    }

    /**
     * เพิ่มแถวที่ยังไม่มี — คืนจำนวนแถวที่สร้างใหม่
     *
     * ปลอดภัยต่อการรันซ้ำ (deploy ทุกรอบ) และไม่ทับแถวที่แอดมินแก้แล้ว
     */
    public static function seed(): int
    {
        if (! Schema::hasTable('fortune_invite_messages')
            || ! Schema::hasColumn('fortune_invite_messages', 'mode')) {
            return 0;
        }

        $hasWindow = FortuneInviteMessage::supportsTimeWindow();
        $sortBase = (int) FortuneInviteMessage::withTrashed()->max('sort_order');
        $created = 0;

        foreach (self::rows() as $row) {
            // ⚠️ withTrashed — แถวที่แอดมินลบไปแล้ว ห้าม seed กลับมา
            $exists = FortuneInviteMessage::withTrashed()
                ->where('mode', FortuneInviteMessage::MODE_WEB)
                ->where('message', $row['message'])
                ->exists();

            if ($exists) {
                continue;
            }

            $data = [
                'message' => $row['message'],
                'category' => $row['category'],
                'mode' => FortuneInviteMessage::MODE_WEB,
                'is_active' => $row['is_active'],
                'sort_order' => $sortBase + $row['number'],
            ];

            if ($hasWindow) {
                $data['hour_from'] = $row['hour_from'];
                $data['hour_to'] = $row['hour_to'];
            }

            FortuneInviteMessage::create($data);
            $created++;
        }

        self::retire();

        return $created;
    }

    /**
     * 🔐 (รีวิว) ปิดแถวที่ยังเป็นถ้อยคำที่เลิกใช้แล้ว (WebHandoffCopy::RETIRED_INVITES) — คืนจำนวนแถวที่ปิด
     *
     * ปิดอย่างเดียว ไม่ลบ (แอดมินเปิดกลับได้ถ้าต้องการ) · แถวที่แอดมินแก้ข้อความแล้วไม่ถูกแตะ
     */
    public static function retire(): int
    {
        if (! Schema::hasTable('fortune_invite_messages')
            || ! Schema::hasColumn('fortune_invite_messages', 'mode')) {
            return 0;
        }

        return FortuneInviteMessage::query()
            ->where('mode', FortuneInviteMessage::MODE_WEB)
            ->whereIn('message', WebHandoffCopy::RETIRED_INVITES)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    /**
     * หมวดของแต่ละข้อ (แอดมินปิดทั้งหมวดได้ที่หน้าข้อความชวน)
     *
     * @param  array{window:string, continuity:bool, topic:?string}  $invite
     */
    protected static function categoryFor(array $invite): string
    {
        if ($invite['continuity']) {
            return 'web-continuity';
        }

        if ($invite['topic'] !== null) {
            return 'web-'.$invite['topic'];
        }

        return match ($invite['window']) {
            WebHandoffCopy::WINDOW_EVENING => 'web-evening',
            WebHandoffCopy::WINDOW_MORNING => 'web-morning',
            default => 'web-room',
        };
    }
}
