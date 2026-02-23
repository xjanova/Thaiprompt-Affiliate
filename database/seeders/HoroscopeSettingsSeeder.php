<?php

namespace Database\Seeders;

use App\Models\FortuneTellingSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างค่าเริ่มต้นสำหรับระบบดูดวงสาธารณะ
 *
 * อัพเดทคอลัมน์ horoscope_* ใน fortune_telling_settings
 */
class HoroscopeSettingsSeeder extends Seeder
{
    /**
     * สร้างค่า default settings สำหรับ horoscope public
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('⚙️ กำลัง seed ตั้งค่าระบบดูดวงสาธารณะ...');

        // ตรวจสอบว่าคอลัมน์มีอยู่แล้วหรือยัง
        if (! Schema::hasColumn('fortune_telling_settings', 'horoscope_public_enabled')) {
            $this->command->warn('⚠️ คอลัมน์ horoscope_public_enabled ยังไม่มี — กรุณา run migration ก่อน');

            return;
        }

        // อัพเดท settings ทุก record ที่มี
        $updated = FortuneTellingSetting::query()->update([
            'horoscope_public_enabled' => true,
            'horoscope_free_daily_limit' => 5,
            'horoscope_dream_free_limit' => 3,
            'horoscope_numerology_free_limit' => 3,
            'horoscope_seo_title_th' => 'ดูดวงออนไลน์ ฟรี — ดวงรายวัน ไพ่ทาโรต์ ทำนายฝัน เลขศาสตร์',
            'horoscope_seo_description_th' => 'ดูดวงออนไลน์ฟรี ดวงรายวัน 12 ราศี ไพ่ทาโรต์ ทำนายฝัน เลขเด็ด วิเคราะห์ชื่อ เบอร์โทร ทะเบียนรถ ทำนายด้วย AI แม่นยำ ทันสมัย',
        ]);

        if ($updated > 0) {
            $this->command->info("✅ อัพเดทตั้งค่าดูดวงสาธารณะ {$updated} records สำเร็จ!");
        } else {
            $this->command->warn('⚠️ ไม่พบ FortuneTellingSetting record — จะอัพเดทเมื่อมี record');
        }
    }
}
