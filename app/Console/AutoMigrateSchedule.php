<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;

/**
 * ตั้งเวลารัน migration ที่ค้าง — เฉพาะร้านลูกค้าที่ไม่ได้ deploy ด้วย deploy.sh
 * และเปิดเองด้วย APP_AUTO_MIGRATE=true (config app.auto_migrate — ค่าเริ่มต้นปิดทุก environment)
 *
 * 🚨 (2026-09-26) มาแทน AppServiceProvider::autoRunPendingMigrations() ที่รัน `migrate --force`
 *    ตอนบูตแอป วันละครั้ง ในทุกโปรเซสที่บูตแอป (php-fpm, cron, artisan ของ deploy.sh):
 *    - deploy.sh: git clean ลบไฟล์ธง migration_check.txt → artisan ตัวแรกหลังนั้น (route:clear
 *      STEP 4.8) รัน migrate ด้วย vendor เก่า (composer อยู่ STEP 6) ก่อน migrate:smart STEP 9
 *      ตัวที่ล้มเหลือแค่ Log::warning แล้ว deploy.sh เห็น "No pending migrations"
 *    - php-fpm: โดน max_execution_time ตัดกลางทาง = migration ครึ่งๆ กลางๆ และไม่มี log เลย
 *    - โปรเซสที่ไปสะดุดหาคำสั่งตัวเองไม่เจอ ("no commands defined in the fortune namespace")
 *    ⇒ prod ปิดไว้ — deploy.sh (migrate:smart) เป็นตัวรัน migration ตัวเดียว
 *      ตัวกัน half-applied ของ migrate:smart จึงครอบได้ครบ
 *
 * เมื่อเปิด: scheduler รัน `migrate --force` เป็นโปรเซสแยกของตัวเอง
 *   (ไม่มี timeout ของเว็บ ไม่แทรกคำสั่งอื่น) ผลลัพธ์ลง storage/logs/auto-migrate.log
 *
 * ⚠️ ห้ามเปิดบนเซิร์ฟเวอร์ที่ deploy ด้วย deploy.sh — จะมีตัวรัน migration 2 ตัวแย่งกัน
 */
class AutoMigrateSchedule
{
    public const NAME = 'auto-migrate';

    /**
     * ลงทะเบียนงาน migrate รายวันใน schedule (เรียกจาก routes/console.php)
     *
     * @return Event|null งานที่ลงทะเบียน หรือ null ถ้าปิดอยู่
     */
    public static function register(Schedule $schedule): ?Event
    {
        if (! config('app.auto_migrate')) {
            return null;
        }

        return $schedule->command('migrate --force')
            ->dailyAt('04:10')
            ->withoutOverlapping(120)
            ->onOneServer()
            ->name(self::NAME)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/auto-migrate.log'))
            ->onFailure(function () {
                Log::warning('Auto-migrate: migrate --force ล้ม — ดูรายละเอียดที่ storage/logs/auto-migrate.log');
            });
    }
}
