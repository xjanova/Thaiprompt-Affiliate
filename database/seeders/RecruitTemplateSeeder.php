<?php

namespace Database\Seeders;

use App\Models\RecruitTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeder สำหรับเทมเพลตหน้า Recruit ค่าเริ่มต้น
 *
 * สร้างเทมเพลตสวยงามพร้อมใช้งาน
 */
class RecruitTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed เทมเพลตหน้า Recruit...');

        // ตรวจสอบว่ามีเทมเพลต default อยู่แล้วหรือไม่
        if (RecruitTemplate::where('is_default', true)->exists()) {
            $this->command->info('⏭️  มีเทมเพลต default อยู่แล้ว ข้าม...');
            return;
        }

        // สร้างเทมเพลต default
        RecruitTemplate::create([
            'title' => 'เทมเพลต Premium - MLM Affiliate',
            'description' => 'เทมเพลตสวยงามสำหรับแม่ทีม MLM พร้อมฟีเจอร์ครบครัน',

            'welcome_message' => '🎯 ยินดีต้อนรับสู่โอกาสทองในการสร้างรายได้!

คุณกำลังจะก้าวเข้าสู่ระบบ MLM ที่ดีที่สุดในประเทศไทย พร้อมระบบสนับสนุนที่ครบครัน และโอกาสในการสร้างรายได้แบบไม่จำกัด!

เริ่มต้นวันนี้ ร่วมเป็นส่วนหนึ่งกับเราตอนนี้!',

            'pitch_message' => 'ระบบ MLM ของเรามีความโดดเด่น:

✅ คอมมิชชั่นสูงสุดในตลาด
✅ ระบบอัตโนมัติ ถอนเงินได้ทันที
✅ ทีมงานซัพพอร์ต 24/7
✅ เครื่องมือการตลาดครบครัน
✅ ชุมชนสมาชิกที่แข็งแกร่ง

เริ่มต้นง่าย ไม่มีค่าใช้จ่าย ไม่มีความเสี่ยง!',

            'benefits' => [
                'รายได้ไม่จำกัด - สร้างทีมได้เท่าไหร่ รายได้เท่านั้น',
                'ระบบอัตโนมัติ - ทำงานให้คุณ 24/7',
                'คอมมิชชั่นหลายระดับ - รับเงินจากทีมทุกชั้น',
                'โบนัสพิเศษ - โบนัสจากยอดขายทีม',
                'เครื่องมือการตลาดฟรี - QR Code, ลิงก์แนะนำ, สถิติ',
                'อบรมฟรี - เทรนนิ่งและสัมมนาประจำ',
                'ชุมชนที่แข็งแกร่ง - เพื่อนร่วมทีมช่วยเหลือกัน',
                'ไม่มีค่าใช้จ่าย - ฟรี 100% ไม่มีค่าสมัคร',
            ],

            'instructions' => [
                [
                    'step' => 1,
                    'title' => 'เพิ่มเพื่อน LINE Official Account',
                    'description' => 'สแกน QR Code หรือกดปุ่มเพิ่มเพื่อน เพื่อรับข้อมูลและการสนับสนุน',
                    'icon' => '💚',
                ],
                [
                    'step' => 2,
                    'title' => 'กดปุ่มสมัครสมาชิก',
                    'description' => 'กรอกข้อมูลพื้นฐาน ใช้เวลาแค่ 2-3 นาที',
                    'icon' => '📝',
                ],
                [
                    'step' => 3,
                    'title' => 'เริ่มต้นแนะนำเพื่อน',
                    'description' => 'ใช้ลิงก์และ QR Code ส่วนตัวของคุณเพื่อเชิญเพื่อนเข้าร่วม',
                    'icon' => '🎯',
                ],
                [
                    'step' => 4,
                    'title' => 'รับคอมมิชชั่นทันที',
                    'description' => 'เมื่อทีมของคุณมียอดขาย คุณจะได้รับคอมมิชชั่นอัตโนมัติ',
                    'icon' => '💰',
                ],
            ],

            'required_steps' => [
                'add_line_friend',
                'fill_profile',
                'verify_phone',
            ],

            'features' => [
                'รับ QR Code และลิงก์ส่วนตัว',
                'ดูสถิติการเข้าชมแบบเรียลไทม์',
                'ติดตามผู้มุ่งหวัง (Leads)',
                'รายงานการตลาดครบถ้วน',
                'เครื่องมือแชร์ Social Media',
            ],

            'require_line_friend' => true,
            'show_team_leader_info' => true,
            'show_statistics' => true,
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->command->info('✅ Seed เทมเพลตหน้า Recruit สำเร็จ!');
    }
}
