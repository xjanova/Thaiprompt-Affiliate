<?php

namespace Database\Seeders;

use App\Models\FortuneSystemVoiceClip;
use Illuminate\Database\Seeder;

/**
 * สร้างคลังเสียงระบบเริ่มต้น (ข้อความกลาง)
 *
 * idempotent: ใช้ updateOrCreate ตาม clip_key — รันซ้ำไม่ซ้ำข้อมูล
 * ⚠️ ไม่ overwrite script_text ที่ admin แก้ไปแล้ว (อัปเดตเฉพาะ field meta คงที่)
 */
class FortuneSystemVoiceClipSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🎧 กำลัง seed คลังเสียงระบบ (system voice clips)...');

        foreach ($this->clips() as $i => $clip) {
            $existing = FortuneSystemVoiceClip::where('clip_key', $clip['clip_key'])->first();

            if ($existing) {
                // มีอยู่แล้ว — อัปเดตเฉพาะ meta ไม่แตะ script_text/enabled ที่ admin อาจแก้
                $existing->update([
                    'step_group' => $clip['step_group'],
                    'title' => $clip['title'],
                    'description' => $clip['description'],
                    'sort_order' => $i,
                ]);

                continue;
            }

            FortuneSystemVoiceClip::create([
                'clip_key' => $clip['clip_key'],
                'step_group' => $clip['step_group'],
                'title' => $clip['title'],
                'description' => $clip['description'],
                'script_text' => $clip['script_text'],
                'enabled' => $clip['enabled'],
                'sort_order' => $i,
            ]);
        }

        $this->command->info('✅ Seed คลังเสียงระบบสำเร็จ!');
    }

    /**
     * รายการคลิปเสียงระบบเริ่มต้น
     *
     * หมายเหตุ: ข้อความเป็น "เสียงกลาง" (ไม่พูดชื่อ/ราคา/ยอดเงิน) — admin แก้ได้ในหน้าจัดการเสียง
     *
     * @return array<int, array<string, mixed>>
     */
    protected function clips(): array
    {
        return [
            // ── ข้อ 1: กล่องกระตุ้นการขาย ──────────────────────────────
            [
                'clip_key' => 'sales_nudge_tier',
                'step_group' => 'sales_nudge',
                'title' => 'กระตุ้นเลือกแพคเกจ',
                'description' => 'ส่งคู่กล่องกระตุ้นตอนลูกค้าค้างขั้นเลือกแพคเกจ',
                'script_text' => 'เลือกแพคเกจที่สนใจได้เลยนะคะ แม่หมอจันทรารออยู่ค่ะ กดปุ่มด้านล่างเพื่อเริ่มดูดวงได้เลยค่ะ',
                'enabled' => true,
            ],
            [
                'clip_key' => 'sales_nudge_consent',
                'step_group' => 'sales_nudge',
                'title' => 'กระตุ้นก่อนบูชาครู',
                'description' => 'ส่งคู่กล่องกระตุ้นตอนลูกค้าค้างก่อนกดพร้อมบูชาครู',
                'script_text' => 'เมื่อพร้อมโอนค่าบูชาครูแล้ว กดปุ่มพร้อมบูชาครูด้านล่างได้เลยนะคะ แล้วระบบจะพาเข้าสู่ขั้นตอนการโอนผ่านคิวอาร์โค้ดหรือธนาคารให้อัตโนมัติค่ะ',
                'enabled' => true,
            ],

            // ── ข้อ 2: กติกาก่อนดูดวง ───────────────────────────────────
            [
                'clip_key' => 'consent_rules',
                'step_group' => 'consent',
                'title' => 'กติกาก่อนดูดวง',
                'description' => 'อ่านกติกา/ค่าครู ก่อนเริ่มทำนาย (ซิงก์จากข้อความกติกาได้)',
                'script_text' => 'ก่อนเริ่มดูดวงกับแม่หมอจันทรา การทำนายนี้มีค่าครูนะคะ แม่หมอจะรอรับค่าครูก่อน แล้วจึงเปิดไพ่ทำนายให้เจ้าชะตา อยากให้แน่ใจก่อนแล้วค่อยกดปุ่มบูชาครูด้านล่างนะคะ พอเริ่มแล้วถามทีละคำถาม ใจเย็นๆ พิมพ์ให้ครบในข้อความเดียว แม่หมอจะดูแลให้เต็มที่ค่ะ',
                'enabled' => true,
            ],

            // ── ข้อ 3: วิธีกรอกวันเกิด ──────────────────────────────────
            [
                'clip_key' => 'howto_birthdate',
                'step_group' => 'birthdate',
                'title' => 'วิธีกรอกวันเดือนปีเกิด',
                'description' => 'อธิบายให้พิมพ์วันเกิดเป็นตัวเลข (พ.ศ.)',
                'script_text' => 'ก่อนเริ่มทำนาย แม่หมอขอวันเดือนปีเกิดของเจ้าชะตาก่อนนะคะ พิมพ์เป็นตัวเลขได้เลย เช่น วันที่สิบสอง เดือนสิบสอง ปีสองพันห้าร้อย ซึ่งเป็นปีพุทธศักราช หรือจะพิมพ์แบบ สิบสอง ทับ สิบสอง ทับ สองห้าศูนย์ศูนย์ ก็ได้ค่ะ ถ้าไม่สะดวกบอก พิมพ์ว่า ข้าม แม่หมอจะดูจากไพ่ให้ค่ะ',
                'enabled' => true,
            ],

            // ── ข้อ 4: กระตุ้นจ่ายหลังมีบิล ────────────────────────────
            [
                'clip_key' => 'payment_reminder',
                'step_group' => 'payment',
                'title' => 'กระตุ้นให้จ่าย (หลังมีบิล)',
                'description' => 'เตือนเมื่อสร้างบิลแล้วลูกค้าเงียบ',
                'script_text' => 'อย่าลืมโอนค่าบูชาครูนะคะ บิลของเจ้าชะตาใกล้จะหมดเวลาแล้ว ถ้าติดขัดตรงไหน หรืออยากให้แม่หมอช่วยแนะนำ พิมพ์บอกได้เลยค่ะ แม่หมอรออยู่นะคะ',
                'enabled' => true,
            ],

            // ── ข้อ 5: จุดที่เสนอเพิ่ม (default ปิด รอ admin เปิด) ──────
            [
                'clip_key' => 'welcome',
                'step_group' => 'welcome',
                'title' => 'ทักทายแรก',
                'description' => 'ทักทายลูกค้าเมื่อเริ่มคุย',
                'script_text' => 'สวัสดีค่ะ แม่หมอจันทรายินดีต้อนรับเจ้าชะตาทุกท่านนะคะ อยากให้แม่หมอดูเรื่องอะไร พิมพ์บอกได้เลยค่ะ',
                'enabled' => false,
            ],
            [
                'clip_key' => 'qr_howto',
                'step_group' => 'payment',
                'title' => 'วิธีสแกน QR / ส่งสลิป',
                'description' => 'อธิบายวิธีโอนหลังส่ง QR',
                'script_text' => 'สแกนคิวอาร์โค้ดนี้เพื่อโอนค่าบูชาครูได้เลยนะคะ เมื่อโอนเรียบร้อยแล้ว ส่งสลิปกลับมาให้แม่หมอ หรือพิมพ์ว่า โอนแล้ว ระบบจะตรวจสอบให้อัตโนมัติค่ะ',
                'enabled' => false,
            ],
            [
                'clip_key' => 'slip_received',
                'step_group' => 'payment',
                'title' => 'ได้รับสลิปแล้ว',
                'description' => 'แจ้งว่าได้รับสลิป กำลังเปิดไพ่',
                'script_text' => 'ได้รับสลิปของเจ้าชะตาแล้วนะคะ แม่หมอกำลังตรวจสอบและเตรียมเปิดไพ่ทำนายให้ รอสักครู่นะคะ',
                'enabled' => false,
            ],
            [
                'clip_key' => 'card_pick_howto',
                'step_group' => 'celtic',
                'title' => 'วิธีตั้งจิตเลือกไพ่',
                'description' => 'อธิบายวิธีตั้งจิตและเลือกไพ่ (Celtic)',
                'script_text' => 'ตั้งจิตให้นิ่ง นึกถึงเรื่องที่อยากรู้ แล้วกดเลือกไพ่ตามใจได้เลยนะคะ ไพ่แต่ละใบจะบอกเล่าเรื่องราวของเจ้าชะตาค่ะ',
                'enabled' => false,
            ],
        ];
    }
}
