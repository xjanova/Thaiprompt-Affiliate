<?php

namespace Database\Seeders;

use App\Models\LineSignupSetting;
use Illuminate\Database\Seeder;

/**
 * LineSignupSettingSeeder
 *
 * สร้างการตั้งค่าเริ่มต้นสำหรับระบบสมัครสมาชิกผ่าน LINE
 */
class LineSignupSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed การตั้งค่า LINE Signup...');

        $settings = $this->getDefaultSettings();

        foreach ($settings as $setting) {
            // ตรวจสอบว่ามีอยู่แล้วหรือไม่ (idempotent)
            if (LineSignupSetting::where('key', $setting['key'])->exists()) {
                $this->command->info("   ⏭️  {$setting['key']} มีอยู่แล้ว ข้าม...");
                continue;
            }

            LineSignupSetting::create($setting);
            $this->command->info("   ✅ สร้าง {$setting['key']}");
        }

        $this->command->info('✅ Seed การตั้งค่า LINE Signup สำเร็จ!');
    }

    /**
     * ดึงการตั้งค่าเริ่มต้นทั้งหมด
     *
     * @return array
     */
    protected function getDefaultSettings(): array
    {
        return [
            // ========================================
            // SIGNUP GROUP - การตั้งค่าทั่วไป
            // ========================================
            [
                'key' => 'signup.enabled',
                'value' => '1',
                'type' => LineSignupSetting::TYPE_BOOLEAN,
                'group' => LineSignupSetting::GROUP_SIGNUP,
                'description' => 'เปิด/ปิดระบบสมัครสมาชิก',
                'is_public' => true,
                'is_editable' => true,
            ],
            [
                'key' => 'signup.maintenance_mode',
                'value' => '0',
                'type' => LineSignupSetting::TYPE_BOOLEAN,
                'group' => LineSignupSetting::GROUP_SIGNUP,
                'description' => 'โหมดปรับปรุงระบบ',
                'is_public' => true,
                'is_editable' => true,
            ],
            [
                'key' => 'signup.maintenance_message',
                'value' => 'ระบบอยู่ระหว่างปรับปรุง กรุณากลับมาใหม่ภายหลัง 🔧',
                'type' => LineSignupSetting::TYPE_STRING,
                'group' => LineSignupSetting::GROUP_SIGNUP,
                'description' => 'ข้อความแจ้งเตือนเมื่ออยู่ในโหมดปรับปรุง',
                'is_public' => true,
                'is_editable' => true,
            ],

            // ========================================
            // OTP GROUP - การตั้งค่า OTP
            // ========================================
            [
                'key' => 'otp.timeout_minutes',
                'value' => '5',
                'type' => LineSignupSetting::TYPE_INTEGER,
                'group' => LineSignupSetting::GROUP_OTP,
                'description' => 'เวลาหมดอายุของ OTP (นาที)',
                'is_public' => false,
                'is_editable' => true,
            ],
            [
                'key' => 'otp.max_attempts',
                'value' => '5',
                'type' => LineSignupSetting::TYPE_INTEGER,
                'group' => LineSignupSetting::GROUP_OTP,
                'description' => 'จำนวนครั้งสูงสุดที่กรอก OTP ผิดได้',
                'is_public' => false,
                'is_editable' => true,
            ],
            [
                'key' => 'otp.provider',
                'value' => 'line',
                'type' => LineSignupSetting::TYPE_STRING,
                'group' => LineSignupSetting::GROUP_OTP,
                'description' => 'ช่องทางส่ง OTP (line, sms)',
                'is_public' => false,
                'is_editable' => true,
            ],

            // ========================================
            // VALIDATION GROUP - การตรวจสอบข้อมูล
            // ========================================
            [
                'key' => 'validation.enable_ai',
                'value' => '1',
                'type' => LineSignupSetting::TYPE_BOOLEAN,
                'group' => LineSignupSetting::GROUP_VALIDATION,
                'description' => 'เปิดใช้ AI สำหรับตรวจสอบชื่อ',
                'is_public' => false,
                'is_editable' => true,
            ],
            [
                'key' => 'validation.min_name_length',
                'value' => '2',
                'type' => LineSignupSetting::TYPE_INTEGER,
                'group' => LineSignupSetting::GROUP_VALIDATION,
                'description' => 'ความยาวชื่อขั้นต่ำ',
                'is_public' => false,
                'is_editable' => true,
            ],
            [
                'key' => 'validation.max_name_length',
                'value' => '100',
                'type' => LineSignupSetting::TYPE_INTEGER,
                'group' => LineSignupSetting::GROUP_VALIDATION,
                'description' => 'ความยาวชื่อสูงสุด',
                'is_public' => false,
                'is_editable' => true,
            ],
            [
                'key' => 'validation.require_full_name',
                'value' => '1',
                'type' => LineSignupSetting::TYPE_BOOLEAN,
                'group' => LineSignupSetting::GROUP_VALIDATION,
                'description' => 'บังคับกรอกทั้งชื่อและนามสกุล',
                'is_public' => false,
                'is_editable' => true,
            ],

            // ========================================
            // REWARDS GROUP - ระบบรางวัล
            // ========================================
            [
                'key' => 'rewards.welcome_bonus_amount',
                'value' => '100',
                'type' => LineSignupSetting::TYPE_INTEGER,
                'group' => LineSignupSetting::GROUP_REWARDS,
                'description' => 'จำนวนเงินโบนัสต้อนรับ (บาท)',
                'is_public' => true,
                'is_editable' => true,
            ],
            [
                'key' => 'rewards.referral_bonus_amount',
                'value' => '50',
                'type' => LineSignupSetting::TYPE_INTEGER,
                'group' => LineSignupSetting::GROUP_REWARDS,
                'description' => 'จำนวนเงินโบนัสแนะนำเพื่อน (บาท)',
                'is_public' => true,
                'is_editable' => true,
            ],
            [
                'key' => 'rewards.auto_grant',
                'value' => '1',
                'type' => LineSignupSetting::TYPE_BOOLEAN,
                'group' => LineSignupSetting::GROUP_REWARDS,
                'description' => 'มอบรางวัลอัตโนมัติทันทีเมื่อสมัครสำเร็จ',
                'is_public' => false,
                'is_editable' => true,
            ],

            // ========================================
            // GAMIFICATION GROUP - เกมมิฟิเคชั่น
            // ========================================
            [
                'key' => 'gamification.enable_milestones',
                'value' => '1',
                'type' => LineSignupSetting::TYPE_BOOLEAN,
                'group' => LineSignupSetting::GROUP_GAMIFICATION,
                'description' => 'เปิดใช้ระบบไมล์สโตน (แบดจ์สมาชิก)',
                'is_public' => false,
                'is_editable' => true,
            ],
            [
                'key' => 'gamification.enable_social_proof',
                'value' => '1',
                'type' => LineSignupSetting::TYPE_BOOLEAN,
                'group' => LineSignupSetting::GROUP_GAMIFICATION,
                'description' => 'แสดงจำนวนสมาชิกที่สมัครแล้ว (Social Proof)',
                'is_public' => false,
                'is_editable' => true,
            ],
            [
                'key' => 'gamification.milestone_tiers',
                'value' => json_encode([1, 10, 50, 100, 500, 1000, 5000, 10000]),
                'type' => LineSignupSetting::TYPE_JSON,
                'group' => LineSignupSetting::GROUP_GAMIFICATION,
                'description' => 'เกณฑ์ไมล์สโตน (จำนวนสมาชิก)',
                'is_public' => false,
                'is_editable' => true,
            ],

            // ========================================
            // SESSION GROUP - การจัดการ Session
            // ========================================
            [
                'key' => 'session.timeout_hours',
                'value' => '24',
                'type' => LineSignupSetting::TYPE_INTEGER,
                'group' => LineSignupSetting::GROUP_SESSION,
                'description' => 'เวลาหมดอายุของ Session (ชั่วโมง)',
                'is_public' => false,
                'is_editable' => true,
            ],
            [
                'key' => 'session.max_abandoned_days',
                'value' => '7',
                'type' => LineSignupSetting::TYPE_INTEGER,
                'group' => LineSignupSetting::GROUP_SESSION,
                'description' => 'จำนวนวันที่เก็บ Session ที่ถูกละทิ้ง',
                'is_public' => false,
                'is_editable' => true,
            ],

            // ========================================
            // INTEGRATION GROUP - การเชื่อมต่อ
            // ========================================
            [
                'key' => 'integration.line_channel_access_token',
                'value' => env('LINE_CHANNEL_ACCESS_TOKEN', ''),
                'type' => LineSignupSetting::TYPE_STRING,
                'group' => LineSignupSetting::GROUP_INTEGRATION,
                'description' => 'LINE Channel Access Token',
                'is_public' => false,
                'is_editable' => true,
            ],
            [
                'key' => 'integration.line_channel_secret',
                'value' => env('LINE_CHANNEL_SECRET', ''),
                'type' => LineSignupSetting::TYPE_STRING,
                'group' => LineSignupSetting::GROUP_INTEGRATION,
                'description' => 'LINE Channel Secret',
                'is_public' => false,
                'is_editable' => true,
            ],
            [
                'key' => 'integration.webhook_url',
                'value' => env('APP_URL', '') . '/api/webhook/line-membership-signup',
                'type' => LineSignupSetting::TYPE_STRING,
                'group' => LineSignupSetting::GROUP_INTEGRATION,
                'description' => 'Webhook URL สำหรับรับ events จาก LINE',
                'is_public' => false,
                'is_editable' => false,
            ],
        ];
    }
}
