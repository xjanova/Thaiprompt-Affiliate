<?php

/**
 * สร้างตาราง video_mission_settings
 *
 * การตั้งค่าระบบภารกิจดูคลิปรับรางวัลทั้งหมด
 * Admin สามารถปรับแต่งค่าต่างๆ ได้
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง video_mission_settings
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('video_mission_settings')) {
            return;
        }

        Schema::create('video_mission_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();                           // ชื่อการตั้งค่า
            $table->text('value')->nullable();                         // ค่า
            $table->string('type')->default('string');                 // ประเภท: string, integer, boolean, json
            $table->string('group')->default('general');               // กลุ่มการตั้งค่า
            $table->string('label')->nullable();                       // ชื่อแสดงผล
            $table->string('label_th')->nullable();                    // ชื่อแสดงผลภาษาไทย
            $table->text('description')->nullable();                   // คำอธิบาย
            $table->text('description_th')->nullable();                // คำอธิบายภาษาไทย
            $table->boolean('is_public')->default(false);              // แสดงให้ผู้ใช้เห็นได้
            $table->timestamps();

            // Index
            $table->index('group');
            $table->index('is_public');
        });

        // สร้างค่าเริ่มต้น
        $this->seedDefaultSettings();
    }

    /**
     * สร้างค่าเริ่มต้นสำหรับการตั้งค่า
     */
    private function seedDefaultSettings(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'system_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'general',
                'label' => 'System Enabled',
                'label_th' => 'เปิดใช้งานระบบ',
                'description' => 'Enable or disable the video mission system',
                'description_th' => 'เปิด/ปิดระบบภารกิจดูคลิปทั้งหมด',
            ],
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'general',
                'label' => 'Maintenance Mode',
                'label_th' => 'โหมดปิดปรับปรุง',
                'description' => 'Put system in maintenance mode',
                'description_th' => 'ปิดระบบเพื่อปรับปรุงชั่วคราว',
            ],
            [
                'key' => 'maintenance_message',
                'value' => 'ระบบกำลังปรับปรุง กรุณากลับมาใหม่ภายหลัง',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Maintenance Message',
                'label_th' => 'ข้อความขณะปิดปรับปรุง',
            ],

            // Anti-Cheat Settings
            [
                'key' => 'anti_cheat_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'anti_cheat',
                'label' => 'Anti-Cheat Enabled',
                'label_th' => 'เปิดระบบป้องกันโกง',
            ],
            [
                'key' => 'heartbeat_interval_seconds',
                'value' => '5',
                'type' => 'integer',
                'group' => 'anti_cheat',
                'label' => 'Heartbeat Interval (seconds)',
                'label_th' => 'ความถี่ heartbeat (วินาที)',
                'description' => 'How often to ping server during video watch',
                'description_th' => 'ความถี่ในการ ping เซิร์ฟเวอร์ขณะดูวิดีโอ',
            ],
            [
                'key' => 'max_heartbeat_miss',
                'value' => '3',
                'type' => 'integer',
                'group' => 'anti_cheat',
                'label' => 'Max Heartbeat Miss',
                'label_th' => 'heartbeat หายได้สูงสุด',
                'description' => 'Maximum missed heartbeats before flagging',
                'description_th' => 'จำนวน heartbeat ที่หายไปได้ก่อนถูกตั้งค่าว่าน่าสงสัย',
            ],
            [
                'key' => 'max_tab_switches',
                'value' => '3',
                'type' => 'integer',
                'group' => 'anti_cheat',
                'label' => 'Max Tab Switches',
                'label_th' => 'สลับ tab ได้สูงสุด',
            ],
            [
                'key' => 'max_seek_forward_seconds',
                'value' => '10',
                'type' => 'integer',
                'group' => 'anti_cheat',
                'label' => 'Max Seek Forward (seconds)',
                'label_th' => 'กระโดดไปข้างหน้าได้สูงสุด (วินาที)',
            ],
            [
                'key' => 'block_dev_tools',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'anti_cheat',
                'label' => 'Block Developer Tools',
                'label_th' => 'บล็อก Developer Tools',
            ],
            [
                'key' => 'suspicious_threshold_score',
                'value' => '70',
                'type' => 'integer',
                'group' => 'anti_cheat',
                'label' => 'Suspicious Threshold Score',
                'label_th' => 'คะแนนน่าสงสัย',
                'description' => 'Score above which user is flagged as suspicious (0-100)',
                'description_th' => 'คะแนนที่เกินแล้วจะถูกตั้งค่าว่าน่าสงสัย (0-100)',
            ],

            // Reward Settings
            [
                'key' => 'default_reward_type',
                'value' => 'coins',
                'type' => 'string',
                'group' => 'rewards',
                'label' => 'Default Reward Type',
                'label_th' => 'ประเภทรางวัลเริ่มต้น',
            ],
            [
                'key' => 'min_withdraw_amount',
                'value' => '100',
                'type' => 'integer',
                'group' => 'rewards',
                'label' => 'Minimum Withdraw Amount',
                'label_th' => 'ยอดถอนขั้นต่ำ',
            ],
            [
                'key' => 'coins_to_thb_rate',
                'value' => '0.01',
                'type' => 'decimal',
                'group' => 'rewards',
                'label' => 'Coins to THB Rate',
                'label_th' => 'อัตราแลก Coins เป็น บาท',
            ],

            // Limit Settings
            [
                'key' => 'default_daily_limit',
                'value' => '10',
                'type' => 'integer',
                'group' => 'limits',
                'label' => 'Default Daily Limit',
                'label_th' => 'ภารกิจต่อวัน (ค่าเริ่มต้น)',
            ],
            [
                'key' => 'default_weekly_limit',
                'value' => '50',
                'type' => 'integer',
                'group' => 'limits',
                'label' => 'Default Weekly Limit',
                'label_th' => 'ภารกิจต่อสัปดาห์ (ค่าเริ่มต้น)',
            ],
            [
                'key' => 'default_monthly_limit',
                'value' => '150',
                'type' => 'integer',
                'group' => 'limits',
                'label' => 'Default Monthly Limit',
                'label_th' => 'ภารกิจต่อเดือน (ค่าเริ่มต้น)',
            ],
            [
                'key' => 'same_video_cooldown_hours',
                'value' => '24',
                'type' => 'integer',
                'group' => 'limits',
                'label' => 'Same Video Cooldown (hours)',
                'label_th' => 'cooldown วิดีโอเดิม (ชั่วโมง)',
            ],

            // Video Settings
            [
                'key' => 'default_required_watch_percentage',
                'value' => '80',
                'type' => 'integer',
                'group' => 'video',
                'label' => 'Default Required Watch Percentage',
                'label_th' => '% ที่ต้องดู (ค่าเริ่มต้น)',
            ],
            [
                'key' => 'allow_speed_change_default',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'video',
                'label' => 'Allow Speed Change (Default)',
                'label_th' => 'อนุญาตเปลี่ยนความเร็ว (ค่าเริ่มต้น)',
            ],
            [
                'key' => 'allowed_video_platforms',
                'value' => '["youtube","vimeo","direct"]',
                'type' => 'json',
                'group' => 'video',
                'label' => 'Allowed Video Platforms',
                'label_th' => 'แพลตฟอร์มวิดีโอที่อนุญาต',
            ],

            // Display Settings
            [
                'key' => 'missions_per_page',
                'value' => '12',
                'type' => 'integer',
                'group' => 'display',
                'label' => 'Missions Per Page',
                'label_th' => 'ภารกิจต่อหน้า',
            ],
            [
                'key' => 'show_completion_stats',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'display',
                'label' => 'Show Completion Stats',
                'label_th' => 'แสดงสถิติการทำสำเร็จ',
            ],
            [
                'key' => 'show_reward_preview',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'display',
                'label' => 'Show Reward Preview',
                'label_th' => 'แสดงตัวอย่างรางวัล',
            ],
        ];

        foreach ($settings as $setting) {
            \DB::table('video_mission_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * ลบตาราง video_mission_settings
     */
    public function down(): void
    {
        Schema::dropIfExists('video_mission_settings');
    }
};
