<?php

namespace Database\Seeders;

use App\Models\LineSignupFlow;
use Illuminate\Database\Seeder;

class LineSignupFlowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * สร้างบทสนทนาสำหรับการสมัครสมาชิกผ่าน LINE Bot
     */
    public function run(): void
    {
        $flows = [
            // Step 1: Welcome Message
            [
                'name' => 'ข้อความต้อนรับ',
                'step_key' => 'welcome',
                'step_order' => 1,
                'message_text' => "🎉 ยินดีต้อนรับสู่ระบบ MLM!\n\nสวัสดีครับ! ผมยินดีที่จะช่วยคุณสมัครสมาชิกกับเรา 😊\n\nคุณได้รับคำเชิญจาก {{sponsor_name}} ให้เข้าร่วมทีมของเรา\n\nเราจะใช้เวลาไม่กี่นาทีในการกรอกข้อมูลของคุณ\n\nพร้อมเริ่มต้นหรือยัง?",
                'input_type' => 'confirm',
                'next_step_key' => 'ask_name',
                'quick_reply_options' => [
                    ['label' => '✅ เริ่มเลย', 'value' => 'ใช่'],
                    ['label' => '❌ ยังไม่พร้อม', 'value' => 'ไม่'],
                ],
                'is_active' => true,
                'is_skippable' => false,
                'require_ai' => false,
            ],

            // Step 2: Ask for Name
            [
                'name' => 'ถามชื่อ-นามสกุล',
                'step_key' => 'ask_name',
                'step_order' => 2,
                'message_text' => "👤 กรอกข้อมูลส่วนตัว\n\nกรุณากรอกชื่อ-นามสกุลของคุณ\n\n📝 ตัวอย่าง: สมชาย ใจดี",
                'input_type' => 'name',
                'next_step_key' => 'ask_phone',
                'validation_rules' => [
                    'min_length' => 2,
                ],
                'validation_error_message' => 'กรุณากรอกชื่อ-นามสกุลให้ถูกต้อง (อย่างน้อย 2 ตัวอักษร)',
                'is_active' => true,
                'is_skippable' => false,
                'require_ai' => false,
            ],

            // Step 3: Ask for Phone
            [
                'name' => 'ถามเบอร์โทรศัพท์',
                'step_key' => 'ask_phone',
                'step_order' => 3,
                'message_text' => "📱 เบอร์โทรศัพท์\n\nกรุณากรอกเบอร์โทรศัพท์มือถือของคุณ\n\n📝 ตัวอย่าง: 0812345678",
                'input_type' => 'phone',
                'next_step_key' => 'ask_email',
                'validation_rules' => [
                    'min_length' => 9,
                    'max_length' => 10,
                ],
                'validation_error_message' => 'กรุณากรอกเบอร์โทรศัพท์ 10 หลัก',
                'is_active' => true,
                'is_skippable' => false,
                'require_ai' => false,
            ],

            // Step 4: Ask for Email (Optional)
            [
                'name' => 'ถามอีเมล (ถ้ามี)',
                'step_key' => 'ask_email',
                'step_order' => 4,
                'message_text' => "📧 อีเมล (ถ้ามี)\n\nกรุณากรอกอีเมลของคุณ หรือพิมพ์ 'ข้าม' เพื่อข้ามขั้นตอนนี้\n\n📝 ตัวอย่าง: example@email.com",
                'input_type' => 'email',
                'next_step_key' => 'confirm_info',
                'is_active' => true,
                'is_skippable' => true,
                'require_ai' => false,
            ],

            // Step 5: Confirm Information
            [
                'name' => 'ยืนยันข้อมูล',
                'step_key' => 'confirm_info',
                'step_order' => 5,
                'message_text' => "✅ ยืนยันข้อมูล\n\nกรุณาตรวจสอบข้อมูลของคุณ:\n\n👤 ชื่อ: {{name}}\n📱 เบอร์โทร: {{phone}}\n📧 อีเมล: {{email}}\n👥 แม่ทีม: {{sponsor_name}}\n\nข้อมูลถูกต้องหรือไม่?",
                'input_type' => 'confirm',
                'next_step_key' => null, // This is the final step
                'quick_reply_options' => [
                    ['label' => '✅ ยืนยัน', 'value' => 'ใช่'],
                    ['label' => '❌ แก้ไข', 'value' => 'ไม่'],
                ],
                'conditional_next_steps' => [
                    [
                        'field' => 'confirmed',
                        'operator' => '==',
                        'value' => 'ไม่',
                        'next_step_key' => 'ask_name', // Go back to ask name
                    ],
                ],
                'is_active' => true,
                'is_skippable' => false,
                'require_ai' => false,
            ],
        ];

        foreach ($flows as $flow) {
            LineSignupFlow::updateOrCreate(
                ['step_key' => $flow['step_key']],
                $flow
            );
        }

        $this->command->info('✅ Line Signup Flow seeded successfully!');
    }
}
