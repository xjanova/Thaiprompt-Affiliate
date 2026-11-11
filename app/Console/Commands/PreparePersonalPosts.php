<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\PersonalPost\PersonalPostPrepService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * เตรียมโพสต์เฟซบุ๊กส่วนตัวล่วงหน้า วันละ 4 โพสต์ จากเทรนด์ Google + ข่าวเทค
 *
 * @example php artisan personal-posts:prepare
 * @example php artisan personal-posts:prepare --date=2026-11-12 --force
 */
class PreparePersonalPosts extends Command
{
    protected $signature = 'personal-posts:prepare
        {--date= : วันที่ (Y-m-d) ค่าเริ่มต้น = วันนี้}
        {--force : เลือกหัวข้อใหม่และสร้างใหม่ทุกช่อง (ยกเว้นช่องที่โพสต์แล้ว)}
        {--ignore-disabled : รันแม้ปิดระบบไว้ในหน้าตั้งค่า}';

    protected $description = 'เตรียมบทความ + ภาพสำหรับโพสต์เฟซบุ๊กส่วนตัว วันละ 4 โพสต์ (เจ้าของกดโพสต์เอง)';

    public function handle(PersonalPostPrepService $service): int
    {
        if (! $this->option('ignore-disabled') && ! (bool) Setting::get(PersonalPostPrepService::SETTING_ENABLED, true)) {
            $this->info('ระบบเตรียมโพสต์ส่วนตัวปิดอยู่ — ข้าม');

            return self::SUCCESS;
        }

        $dateOption = $this->option('date');
        try {
            $date = $dateOption ? Carbon::createFromFormat('Y-m-d', $dateOption)->startOfDay() : now()->startOfDay();
        } catch (\Throwable) {
            $this->error('รูปแบบวันที่ไม่ถูกต้อง ใช้ Y-m-d เช่น 2026-11-12');

            return self::INVALID;
        }

        $this->info("🗞️ กำลังเตรียมโพสต์ส่วนตัวของวันที่ {$date->toDateString()} ...");
        $stats = $service->prepareDay($date, (bool) $this->option('force'));

        $this->info("✅ สร้างใหม่ {$stats['created']} · พร้อมโพสต์ {$stats['ready']} · ล้ม {$stats['failed']} · ข้าม {$stats['skipped']}");

        return $stats['failed'] > 0 && $stats['ready'] === 0 && $stats['created'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
