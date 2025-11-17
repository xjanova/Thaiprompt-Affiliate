<?php

namespace Database\Seeders;

use App\Models\LineSignupFlow;
use Illuminate\Database\Seeder;

class LineSignupFlowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * สร้างขั้นตอนการสมัครสมาชิกแบบครบถ้วน (LINE Bot Signup Flow)
     *
     * ขั้นตอนการทำงาน:
     * 1. welcome - ยินดีต้อนรับและอธิบายข้อมูล
     * 2. phone - ขอเบอร์โทรศัพท์
     * 3. email - ขอที่อยู่อีเมล
     * 4. full_name - ขอชื่อ-นามสกุล
     * 5. address - ขอที่อยู่
     * 6. consent - ขอยินยอมข้อมูลส่วนบุคคล
     * 7. verification_code - ยืนยันรหัส OTP (optional)
     * 8. completion - ยืนยันและสรุปข้อมูล
     * 9. success - สมัครสำเร็จ
     *
     * @return void
     */
    public function run(): void
    {
        // ตรวจสอบว่า flows มีอยู่แล้วหรือไม่
        $existingFlows = LineSignupFlow::count();

        if ($existingFlows > 0) {
            $this->command->warn('⚠️  LINE Signup Flows already exist!');
            $this->command->info('   Skipping to preserve your custom configurations.');
            return;
        }

        $this->command->info('🌱 สร้าง LINE Signup Flows...');

        // ขั้นตอนที่ 1: ยินดีต้อนรับ
        LineSignupFlow::create([
            'name' => 'ขั้นตอนที่ 1: ยินดีต้อนรับ',
            'step_key' => 'welcome',
            'step_order' => 1,
            'message_text' => '👋 สวัสดีครับ! ยินดีต้อนรับสู่ระบบ Affiliate

ขณะนี้เราจะทำการสมัครสมาชิกให้คุณ โปรดกรุณาตอบคำถามตามลำดับต่อไปนี้

📋 ข้อมูลที่เราต้องการ:
• เบอร์โทรศัพท์
• อีเมล
• ชื่อ-นามสกุล
• ที่อยู่
• ยินยอมข้อมูลส่วนบุคคล

กรุณากด "เริ่มต้น" เพื่อสมัครสมาชิก',
            'input_type' => 'none',
            'validation_rules' => [],
            'next_step_key' => 'phone',
            'quick_reply_options' => [
                ['label' => '🚀 เริ่มต้น', 'value' => 'เริ่มต้น'],
                ['label' => '❓ ถามรายละเอียด', 'value' => 'ถามรายละเอียด'],
            ],
            'is_active' => true,
            'is_skippable' => false,
            'require_ai' => false,
        ]);

        // ขั้นตอนที่ 2: เบอร์โทรศัพท์
        LineSignupFlow::create([
            'name' => 'ขั้นตอนที่ 2: เบอร์โทรศัพท์',
            'step_key' => 'phone',
            'step_order' => 2,
            'message_text' => '📱 ขอเบอร์โทรศัพท์ของคุณด้วยครับ

กรุณากรอกเบอร์โทรศัพท์ (9-10 หลัก)
เช่น 0891234567 หรือ 062-1234567',
            'input_type' => 'phone',
            'validation_rules' => [
                'required' => true,
                'regex' => '/^(0[6-9][0-9]{7,8}|[0-9]{9,10})$/',
            ],
            'validation_error_message' => '❌ เบอร์โทรศัพท์ไม่ถูกต้อง กรุณากรอกใหม่ (9-10 หลัก)',
            'next_step_key' => 'email',
            'quick_reply_options' => [
                ['label' => '📞 ส่งเบอร์', 'value' => 'ส่งเบอร์'],
            ],
            'is_active' => true,
            'is_skippable' => false,
            'require_ai' => false,
        ]);

        // ขั้นตอนที่ 3: อีเมล
        LineSignupFlow::create([
            'name' => 'ขั้นตอนที่ 3: อีเมล',
            'step_key' => 'email',
            'step_order' => 3,
            'message_text' => '📧 ขอที่อยู่อีเมลของคุณด้วยครับ

กรุณากรอกอีเมลที่ใช้งาน
เช่น yourname@gmail.com',
            'input_type' => 'email',
            'validation_rules' => [
                'required' => true,
                'regex' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
            ],
            'validation_error_message' => '❌ รูปแบบอีเมลไม่ถูกต้อง กรุณากรอกใหม่',
            'next_step_key' => 'full_name',
            'quick_reply_options' => [
                ['label' => '📧 ส่งอีเมล', 'value' => 'ส่งอีเมล'],
            ],
            'is_active' => true,
            'is_skippable' => false,
            'require_ai' => true,
            'ai_prompt' => 'ตรวจสอบว่าอีเมลที่ส่งมามีรูปแบบถูกต้องหรือไม่ ถ้าไม่ถูกต้องให้ให้คำแนะนำ',
            'ai_context' => [
                'description' => 'ตรวจสอบและตรวจสอบความถูกต้องของอีเมล',
            ],
        ]);

        // ขั้นตอนที่ 4: ชื่อ-นามสกุล
        LineSignupFlow::create([
            'name' => 'ขั้นตอนที่ 4: ชื่อ-นามสกุล',
            'step_key' => 'full_name',
            'step_order' => 4,
            'message_text' => '👤 ขอชื่อ-นามสกุลของคุณด้วยครับ

กรุณากรอกชื่อเต็ม
เช่น สมชาย ใจดี',
            'input_type' => 'name',
            'validation_rules' => [
                'required' => true,
                'min_length' => 3,
                'max_length' => 100,
            ],
            'validation_error_message' => '❌ ชื่อต้องมีอย่างน้อย 3 ตัวอักษร กรุณากรอกใหม่',
            'next_step_key' => 'address',
            'quick_reply_options' => [
                ['label' => '👤 ส่งชื่อ', 'value' => 'ส่งชื่อ'],
            ],
            'is_active' => true,
            'is_skippable' => false,
            'require_ai' => true,
            'ai_prompt' => 'ตรวจสอบว่าชื่อที่ส่งมามีความเหมาะสมหรือไม่ และชื่อดูสมเด็จหรือไม่',
            'ai_context' => [
                'description' => 'ตรวจสอบความสมเด็จของชื่อ-นามสกุล',
            ],
        ]);

        // ขั้นตอนที่ 5: ที่อยู่
        LineSignupFlow::create([
            'name' => 'ขั้นตอนที่ 5: ที่อยู่',
            'step_key' => 'address',
            'step_order' => 5,
            'message_text' => '🏠 ขอที่อยู่ของคุณด้วยครับ

กรุณากรอกที่อยู่ปัจจุบัน เช่น:
เลขที่ 123 หมู่ 4 ตำบล... อำเภอ... จังหวัด...

(หรือสามารถกรอก เลขที่ ซ.ถ. อ.ร. จว. รหัสไปรษณีย์ก็ได้)',
            'input_type' => 'text',
            'validation_rules' => [
                'required' => true,
                'min_length' => 5,
            ],
            'validation_error_message' => '❌ ที่อยู่ต้องมีอย่างน้อย 5 ตัวอักษร กรุณากรอกใหม่',
            'next_step_key' => 'consent',
            'quick_reply_options' => [
                ['label' => '🏠 ส่งที่อยู่', 'value' => 'ส่งที่อยู่'],
            ],
            'is_active' => true,
            'is_skippable' => false,
            'require_ai' => false,
        ]);

        // ขั้นตอนที่ 6: ยินยอมข้อมูลส่วนบุคคล
        LineSignupFlow::create([
            'name' => 'ขั้นตอนที่ 6: ยินยอมข้อมูลส่วนบุคคล',
            'step_key' => 'consent',
            'step_order' => 6,
            'message_text' => '📋 ขอยินยอมในการใช้ข้อมูลส่วนบุคคล

เราใช้ข้อมูลของคุณเพื่อ:
• สมัครสมาชิก
• ติดต่อและสนับสนุน
• ส่งข้อมูลข่าวสารและโปรโมชัน

✅ คุณยินยอมให้เราใช้ข้อมูลของคุณหรือไม่?',
            'input_type' => 'confirm',
            'validation_rules' => [],
            'next_step_key' => 'completion',
            'quick_reply_options' => [
                ['label' => '✅ ยินยอม', 'value' => 'ยินยอม'],
                ['label' => '❌ ไม่ยินยอม', 'value' => 'ไม่ยินยอม'],
            ],
            'conditional_next_steps' => [
                [
                    'field' => 'consent',
                    'operator' => '==',
                    'value' => 'ไม่ยินยอม',
                    'next_step_key' => 'cancel',
                ],
            ],
            'is_active' => true,
            'is_skippable' => false,
            'require_ai' => false,
        ]);

        // ขั้นตอนที่ 7: สรุปข้อมูลและยืนยัน
        LineSignupFlow::create([
            'name' => 'ขั้นตอนที่ 7: สรุปข้อมูล',
            'step_key' => 'completion',
            'step_order' => 7,
            'message_text' => '📝 กรุณายืนยันข้อมูลของคุณ:

📱 เบอร์โทร: {{phone}}
📧 อีเมล: {{email}}
👤 ชื่อ: {{full_name}}
🏠 ที่อยู่: {{address}}

✅ ข้อมูลถูกต้องหรือไม่?',
            'input_type' => 'confirm',
            'validation_rules' => [],
            'next_step_key' => 'success',
            'quick_reply_options' => [
                ['label' => '✅ ถูกต้อง สมัครเลย', 'value' => 'สมัคร'],
                ['label' => '🔄 แก้ไขข้อมูล', 'value' => 'แก้ไข'],
            ],
            'conditional_next_steps' => [
                [
                    'field' => 'completion',
                    'operator' => '==',
                    'value' => 'แก้ไข',
                    'next_step_key' => 'phone',
                ],
            ],
            'is_active' => true,
            'is_skippable' => false,
            'require_ai' => false,
        ]);

        // ขั้นตอนที่ 8: สมัครสำเร็จ
        LineSignupFlow::create([
            'name' => 'ขั้นตอนที่ 8: สมัครสำเร็จ',
            'step_key' => 'success',
            'step_order' => 8,
            'message_text' => '🎉 ยินดีด้วย! สมัครสมาชิกสำเร็จแล้ว!

🆔 รหัสสมาชิก: {{member_id}}
🎫 รหัสแนะนำ: {{referral_code}}

📌 ขั้นตอนถัดไป:
1️⃣ เข้าสู่ระบบด้วยอีเมลและรหัสผ่าน
2️⃣ อัพเดทโปรไฟล์ของคุณ
3️⃣ เริ่มแชร์ลิงก์แนะนำเพื่อน
4️⃣ รับรายได้และโบนัส

💬 หากมีคำถามสามารถติดต่อเราได้ทุกเมื่อ!

🚀 เข้าสู่ระบบเลย: https://your-domain.com/login',
            'input_type' => 'none',
            'validation_rules' => [],
            'next_step_key' => null,
            'is_active' => true,
            'is_skippable' => true,
            'require_ai' => false,
        ]);

        // ขั้นตอนที่ 9: ยกเลิกการสมัคร (สำเร็จ)
        LineSignupFlow::create([
            'name' => 'ขั้นตอนที่ 9: ยกเลิก',
            'step_key' => 'cancel',
            'step_order' => 9,
            'message_text' => '❌ ขออภัยครับ คุณไม่ได้ยินยอมให้เราใช้ข้อมูลส่วนบุคคล

ดังนั้นเราจึงไม่สามารถสมัครสมาชิกให้คุณได้

หากต้องการสมัครใหม่ กรุณาติดต่อเราทาง:
📞 support@example.com
📧 ติดต่อแอดมิน',
            'input_type' => 'none',
            'validation_rules' => [],
            'next_step_key' => null,
            'is_active' => true,
            'is_skippable' => true,
            'require_ai' => false,
        ]);

        $this->command->info('✅ LINE Signup Flows สร้างสำเร็จ! (8 ขั้นตอน)');
        $this->command->line('');
        $this->command->info('📋 ขั้นตอนการสมัคร:');
        $this->command->line('  1. welcome      - ยินดีต้อนรับ');
        $this->command->line('  2. phone        - เบอร์โทรศัพท์');
        $this->command->line('  3. email        - อีเมล');
        $this->command->line('  4. full_name    - ชื่อ-นามสกุล');
        $this->command->line('  5. address      - ที่อยู่');
        $this->command->line('  6. consent      - ยินยอมข้อมูล');
        $this->command->line('  7. completion   - สรุปข้อมูล');
        $this->command->line('  8. success      - สมัครสำเร็จ');
        $this->command->line('');
        $this->command->info('💡 สามารถแก้ไขขั้นตอนได้ที่ /admin/line-signup-flow/');
    }
}
