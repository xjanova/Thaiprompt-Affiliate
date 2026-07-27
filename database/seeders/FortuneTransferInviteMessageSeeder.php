<?php

namespace Database\Seeders;

use App\Models\FortuneInviteMessage;
use Illuminate\Database\Seeder;

/**
 * ข้อความชวนดูดวงชุด "โหมด transfer" (พาไปเว็บ/LINE)
 *
 * 🔀 (2026-07-28) เจ้าของสั่งข้อ 8: ข้อความที่ไม่สอดคล้องกับโหมดใหม่ต้องแก้ให้หมด
 * แต่ **ห้ามเขียนทับ** ชุดเดิม 100 ข้อความที่เจ้าของคัดมาเอง (สลับกลับ classic ต้องได้ของเดิม)
 * → ชุดนี้เป็นแถวใหม่ mode='transfer' ทั้งหมด ชุดเดิมไม่ถูกแตะเลย
 *
 * กติกาการเขียนข้อความชุดนี้:
 *   - ห้ามชวน "ทักมาดูดวงในแชท" / ห้ามบอกราคา / ห้ามบอกวิธีโอน (บริการอยู่ปลายทางแล้ว)
 *   - ใช้ {{web_link}} เสมอ (magic link สร้างต่อคน — ฝังลิงก์ตายตัวไม่ได้)
 *   - {{line_link}} ใส่เป็นทางเลือกรอง — ถ้าไม่ได้ตั้ง LINE OA ระบบจะตัดบรรทัดนั้นทิ้งเอง
 *   - โทนเดียวกับแม่หมอ: หญิง ไม่ใช้ "ครับ/ผม" (rule_mae_mor_female_only)
 *
 * ⚠️ ชุดนี้เป็น "ชุดตั้งต้น" ให้เปิดโหมดแล้วเสียงไม่สวนกับกล่องทันที
 *    เจ้าของแก้/เพิ่มเองได้ที่ Admin → ข้อความชวนดูดวง
 */
class FortuneTransferInviteMessageSeeder extends Seeder
{
    /**
     * สร้างข้อความชุดโหมด transfer
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อความชวนดูดวงชุดโหมด transfer...');

        // ✅ idempotent — มีชุดนี้แล้วข้ามเลย (ห้ามสร้างซ้ำทุกครั้งที่ deploy)
        if (FortuneInviteMessage::where('mode', FortuneInviteMessage::MODE_TRANSFER)->exists()) {
            $this->command->info('   มีข้อความชุด transfer อยู่แล้ว ข้าม...');

            return;
        }

        $messages = [
            [
                'category' => 'transfer-free',
                'message' => "🌙 แม่หมอเปิดไพ่ให้ฟรี 1 ใบนะ{name}\n\n"
                    ."ไม่ต้องสมัคร ไม่ต้องกรอกอะไร กดแล้วเข้าให้เองเลย\n"
                    ."👉 {{web_link}}\n"
                    .'💚 หรือทาง LINE ก็ได้ค่ะ: {{line_link}}',
            ],
            [
                'category' => 'transfer-free',
                'message' => "✨ ช่วงนี้ดวงกำลังพลิก {name} อยากรู้ไหมว่าพลิกไปทางไหน\n\n"
                    ."แม่หมอเปิดไพ่ให้ดูฟรี 1 ใบ กดตรงนี้ได้เลย\n"
                    .'👉 {{web_link}}',
            ],
            [
                'category' => 'transfer-free',
                'message' => "🔮 ไพ่ใบแรกของ{name} แม่หมอเปิดให้ฟรีค่ะ\n\n"
                    ."เห็นภาพรวมชีวิตช่วงนี้ก่อน แล้วค่อยตัดสินใจว่าจะดูลึกต่อไหม\n"
                    ."👉 {{web_link}}\n"
                    .'💚 ถนัด LINE มากกว่า แอดมาทางนี้: {{line_link}}',
            ],
            [
                'category' => 'transfer-move',
                'message' => "🌙 บอกไว้ก่อนนะ{name} — ตอนนี้แม่หมอย้ายไปดูให้ที่เว็บกับ LINE แล้วค่ะ\n\n"
                    ."ที่นั่นคำทำนายเก็บไว้ให้ ย้อนดูได้ทุกเมื่อ ไม่หายเหมือนในแชท\n"
                    .'👉 {{web_link}}',
            ],
            [
                'category' => 'transfer-move',
                'message' => "✨ {name} คะ แม่หมอมีที่ทางใหม่ให้แล้ว\n\n"
                    ."เปิดไพ่ได้เต็มที่ เก็บประวัติให้ครบ มีกระเป๋าเงินของตัวเอง\n"
                    ."และมีผังสายงานสำหรับคนที่อยากชวนเพื่อนด้วยค่ะ\n"
                    .'👉 {{web_link}}',
            ],
            [
                'category' => 'transfer-timing',
                'message' => "⏳ บางเรื่องถ้ารู้ช้าไปหนึ่งก้าว มันแก้ไม่ทันนะ{name}\n\n"
                    ."แม่หมอเปิดไพ่ให้ฟรี 1 ใบ ดูจังหวะช่วงนี้ก่อน\n"
                    .'👉 {{web_link}}',
            ],
            [
                'category' => 'transfer-timing',
                'message' => "🌙 คืนนี้ดาวเดินเปลี่ยนแล้ว{name}\n\n"
                    ."อยากรู้ว่ามันเดินเข้าหรือเดินออกจากดวงเรา — เปิดไพ่ดูสักใบ ฟรีค่ะ\n"
                    .'👉 {{web_link}}',
            ],
            [
                'category' => 'transfer-love',
                'message' => "💗 คนที่{name}คิดถึงอยู่ เขายังคิดถึงเราไหม\n\n"
                    ."ไพ่ใบเดียวก็บอกได้ค่ะ แม่หมอเปิดให้ฟรี\n"
                    ."👉 {{web_link}}\n"
                    .'💚 หรือคุยกันทาง LINE: {{line_link}}',
            ],
            [
                'category' => 'transfer-money',
                'message' => "💰 การเงินช่วงนี้ติดขัดหรือกำลังจะคล่อง{name}\n\n"
                    ."แม่หมอเปิดไพ่ให้ดูฟรี 1 ใบ รู้ก่อนวางแผนได้ก่อนค่ะ\n"
                    .'👉 {{web_link}}',
            ],
            [
                'category' => 'transfer-work',
                'message' => "🌿 งานที่ทำอยู่ ควรอดทนต่อหรือถึงเวลาขยับ{name}\n\n"
                    ."ไพ่ใบแรกแม่หมอเปิดให้ฟรีค่ะ ดูจังหวะก่อนตัดสินใจ\n"
                    .'👉 {{web_link}}',
            ],
            [
                'category' => 'transfer-easy',
                'message' => "🌙 กลัวว่าจะทำไม่เป็นใช่ไหม{name}\n\n"
                    ."ไม่ยากเลยค่ะ กดลิงก์นี้ปุ๊บ เข้าให้เองเลย ไม่ต้องสมัคร ไม่ต้องกรอกอะไร\n"
                    ."👉 {{web_link}}\n"
                    .'ถ้าติดตรงไหน ทักบอกแม่หมอได้นะคะ เดี๋ยวช่วยดูให้',
            ],
            [
                'category' => 'transfer-easy',
                'message' => "✨ {name} คะ กดครั้งเดียวก็เห็นไพ่แล้ว\n\n"
                    ."ไม่มีขั้นตอนยุ่งยาก ไม่ต้องจำรหัสอะไรทั้งนั้น\n"
                    ."👉 {{web_link}}\n"
                    .'💚 ถนัด LINE ก็แอดทางนี้ได้ค่ะ: {{line_link}}',
            ],
        ];

        $sortBase = (int) FortuneInviteMessage::max('sort_order');

        foreach ($messages as $index => $row) {
            FortuneInviteMessage::create([
                'message' => $row['message'],
                'category' => $row['category'],
                'mode' => FortuneInviteMessage::MODE_TRANSFER,
                'is_active' => true,
                'sort_order' => $sortBase + $index + 1,
            ]);
        }

        $this->command->info('✅ Seed ข้อความชุด transfer สำเร็จ ('.count($messages).' ข้อความ)');
    }
}
