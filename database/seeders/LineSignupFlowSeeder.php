<?php

namespace Database\Seeders;

use App\Models\LineSignupFlow;
use Illuminate\Database\Seeder;

class LineSignupFlowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * สร้างขั้นตอนการสมัครสมาชิก LINE Bot (Simplified Flow)
     *
     * ขั้นตอนการทำงาน (4 steps):
     * 1. welcome - ยินดีต้อนรับและแสดงข้อมูล LINE
     * 2. sponsor_code (optional) - รหัสผู้แนะนำ (ข้ามได้)
     * 3. consent - ยินยอมข้อมูลส่วนบุคคล
     * 4. success - สมัครสำเร็จ
     *
     * Note: Flow นี้ใช้ข้อมูลจาก LINE เป็นหลัก
     * - ชื่อ: ใช้ LINE displayName
     * - รูปโปรไฟล์: ใช้ LINE pictureUrl
     * - เบอร์โทร/อีเมล: ค่อยเพิ่มภายหลังในระบบ
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

        $this->command->info('🌱 สร้าง LINE Signup Flows (Simplified)...');

        // ขั้นตอนที่ 1: ยินดีต้อนรับ + แสดงข้อมูล LINE
        LineSignupFlow::create([
            'name' => 'ขั้นตอนที่ 1: ยินดีต้อนรับ',
            'step_key' => 'welcome',
            'step_order' => 1,
            'message_text' => '👋 ยินดีต้อนรับ {prospect_name}!

📸 เราได้รับข้อมูลจาก LINE ของคุณแล้ว:
• ชื่อ: {prospect_name}
• รูปโปรไฟล์: มี ✅

ต้องการสมัครสมาชิกใช่ไหม?

💡 หลังสมัครเสร็จ คุณสามารถ:
✅ เข้าระบบด้วย LINE ได้ทันที
✅ เพิ่มข้อมูลเพิ่มเติมในระบบ
✅ เริ่มใช้งานได้ทันที',
            'input_type' => 'none',
            'validation_rules' => [],
            'next_step_key' => 'sponsor_code',
            'quick_reply_options' => [
                ['label' => '✅ สมัครเลย', 'value' => 'สมัคร'],
                ['label' => '❌ ยกเลิก', 'value' => 'ยกเลิก'],
            ],
            'is_active' => true,
            'is_skippable' => false,
            'require_ai' => false,
        ]);

        // ขั้นตอนที่ 2: รหัสผู้แนะนำ (Optional)
        LineSignupFlow::create([
            'name' => 'ขั้นตอนที่ 2: รหัสผู้แนะนำ (Optional)',
            'step_key' => 'sponsor_code',
            'step_order' => 2,
            'message_text' => '👥 มีรหัสผู้แนะนำหรือไม่?

หากคุณมีรหัสผู้แนะนำจากทีมของคุณ กรุณากรอกที่นี่
เช่น: ABC12345 หรือ REF-001

💡 ถ้าไม่มีรหัสผู้แนะนำ สามารถกด "ข้าม" ได้เลย
ระบบจะจัดทีมให้อัตโนมัติตามการตั้งค่า',
            'input_type' => 'text',
            'validation_rules' => [
                'required' => false,
                'min_length' => 3,
                'max_length' => 50,
            ],
            'validation_error_message' => '❌ รหัสผู้แนะนำไม่ถูกต้อง กรุณากรอกใหม่ (3-50 ตัวอักษร)',
            'next_step_key' => 'consent',
            'quick_reply_options' => [
                ['label' => '⏭️ ข้าม (ไม่มี)', 'value' => 'ข้าม'],
                ['label' => '👥 กรอกรหัส', 'value' => 'กรอก'],
            ],
            'is_active' => true,
            'is_skippable' => true,
            'require_ai' => false,
        ]);

        // ขั้นตอนที่ 3: ยินยอมข้อมูลส่วนบุคคล (PDPA)
        LineSignupFlow::create([
            'name' => 'ขั้นตอนที่ 3: ยินยอมข้อมูลส่วนบุคคล',
            'step_key' => 'consent',
            'step_order' => 3,
            'message_text' => '📋 ยินยอมการใช้ข้อมูลส่วนบุคคล

เราจะใช้ข้อมูลของคุณเพื่อ:
• สมัครสมาชิก
• ติดต่อและสนับสนุน
• ส่งข้อมูลข่าวสารและโปรโมชัน

✅ คุณยินยอมให้เราใช้ข้อมูลของคุณหรือไม่?',
            'input_type' => 'confirm',
            'validation_rules' => [],
            'next_step_key' => 'success',
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

        // ขั้นตอนที่ 4: สมัครสำเร็จ
        LineSignupFlow::create([
            'name' => 'ขั้นตอนที่ 4: สมัครสำเร็จ',
            'step_key' => 'success',
            'step_order' => 4,
            'message_text' => '🎉 ยินดีด้วย! สมัครสมาชิกสำเร็จแล้ว!

📋 ข้อมูลของคุณ:
• ชื่อ: {prospect_name}
• รหัสสมาชิก: {member_code}
• รหัสแนะนำ: {referral_code}
• ผู้แนะนำ: {sponsor_name}

🚀 เข้าสู่ระบบได้เลยที่นี่:
{dashboard_link}

💡 ล็อกอินด้วย LINE ได้ทันที!
กดลิงก์ด้านบนเพื่อเริ่มใช้งาน

📝 อย่าลืม:
• เพิ่มเบอร์โทรศัพท์ในโปรไฟล์
• เพิ่มที่อยู่สำหรับจัดส่ง
• เพิ่มข้อมูลธนาคารรับเงิน',
            'input_type' => 'none',
            'validation_rules' => [],
            'next_step_key' => null,
            'is_active' => true,
            'is_skippable' => true,
            'require_ai' => false,
        ]);

        // ขั้นตอนยกเลิก (สำหรับกรณีไม่ยินยอม)
        LineSignupFlow::create([
            'name' => 'ยกเลิกการสมัคร',
            'step_key' => 'cancel',
            'step_order' => 5,
            'message_text' => '❌ ขออภัยครับ คุณไม่ได้ยินยอมให้เราใช้ข้อมูลส่วนบุคคล

ดังนั้นเราจึงไม่สามารถสมัครสมาชิกให้คุณได้

หากต้องการสมัครใหม่ กรุณาพิมพ์ "สมัครสมาชิก" อีกครั้ง',
            'input_type' => 'none',
            'validation_rules' => [],
            'next_step_key' => null,
            'is_active' => true,
            'is_skippable' => true,
            'require_ai' => false,
        ]);

        $this->command->info('✅ LINE Signup Flows สร้างสำเร็จ! (4 ขั้นตอน + 1 ยกเลิก)');
        $this->command->line('');
        $this->command->info('📋 ขั้นตอนการสมัคร (Simplified):');
        $this->command->line('  1. welcome        - ยินดีต้อนรับ + แสดงข้อมูล LINE');
        $this->command->line('  2. sponsor_code   - รหัสผู้แนะนำ (Optional)');
        $this->command->line('  3. consent        - ยินยอม PDPA');
        $this->command->line('  4. success        - สมัครสำเร็จ');
        $this->command->line('');
        $this->command->info('💡 Flow ใหม่นี้ใช้ข้อมูลจาก LINE เป็นหลัก');
        $this->command->info('   ผู้ใช้สามารถเพิ่มข้อมูลเพิ่มเติม (เบอร์โทร/อีเมล) ภายหลังในระบบ');
    }
}
