<?php

namespace Database\Seeders;

use App\Models\FortuneTellingSetting;
use Illuminate\Database\Seeder;

/**
 * Fortune Telling Setting Seeder
 *
 * สร้างข้อมูลเริ่มต้นสำหรับการตั้งค่าระบบดูดวง
 */
class FortuneTellingSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูลการตั้งค่าระบบดูดวง...');

        // ✅ ตรวจสอบก่อนสร้าง (idempotent)
        if (FortuneTellingSetting::count() > 0) {
            $this->command->info('ข้อมูลการตั้งค่ามีอยู่แล้ว ข้าม...');

            return;
        }

        FortuneTellingSetting::create([
            'use_global_ai_settings' => true, // ใช้ AI Config จากระบบหลักโดยค่าเริ่มต้น
            'ai_provider' => 'gemini',
            'ai_model' => 'gemini-2.0-flash-exp',
            'max_free_readings' => 3,
            'reading_price' => 0,
            'is_enabled' => false, // ปิดก่อน จนกว่าจะตั้งค่า
            'respond_in_comment' => false,
            'require_registration' => false,
            'prompt_template' => <<<'EOT'
คุณเป็นหมอดูมืออาชีพที่มีความสามารถในการวิเคราะห์ดวงชะตาและทำนายอนาคต

ข้อมูลผู้ถาม:
{user_profile}

บริบทจากโพสล่าสุด:
{user_posts}

คำถามที่ต้องการทำนาย:
{questions}

กรุณาทำนายอย่างละเอียด แต่กระชับ โดยใช้ภาษาไทยที่เข้าใจง่าย
แต่ละคำถามควรได้รับคำตอบอย่างน้อย 2-3 ประโยค
ใช้น้ำเสียงที่อบอุ่น เป็นกันเอง และให้กำลังใจ
EOT,
            'welcome_message' => "🔮 ยินดีต้อนรับสู่ระบบดูดวง\n\nพิมพ์: ดูดวง ตามด้วยคำถาม 1-3 ข้อ\nตัวอย่าง: ดูดวง เรื่องความรัก, เรื่องการเงิน, เรื่องสุขภาพ",
            'limit_exceeded_message' => "คุณได้ใช้งานครบจำนวนฟรีวันนี้แล้ว\n\nสมัครสมาชิกเพื่อใช้งานไม่จำกัด",
            'payment_message' => "💰 ชำระเงินเพื่อใช้งานต่อ\n\nโอนเงินผ่าน QR Code ด้านล่าง",
        ]);

        $this->command->info('✅ Seed ข้อมูลการตั้งค่าระบบดูดวงสำเร็จ!');
    }
}
