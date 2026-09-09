<?php

namespace App\Console\Commands;

use App\Services\HoroscopeDailyService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * สร้างดวงรายวันอัตโนมัติ — 7+1 วันเกิด (7 วัน + พุธกลางคืน)
 *
 * ใช้กับ Laravel Scheduler:
 * - php artisan horoscope:generate-daily          (สร้างดวงวันนี้)
 * - php artisan horoscope:generate-daily --date=2026-02-25 (วันที่เฉพาะ)
 *
 * 🗑️ (2026-09-09) ถอดเลน 12 ราศี ออก — ตาราง horoscope_zodiac_signs
 * บน prod = 0 แถว (ไม่เคยรัน seeder) ลูปจึงวนบน collection ว่างมาตลอด
 * ถ้ามีคน seed ทีหลังโดยไม่ตั้งใจ มันจะเผา AI เงียบ ๆ วันละ 12 ใบ
 *
 * ตั้ง cron ทุกวัน 00:01 น. (2026-08-06 ย้ายจาก 06:00 — ปิดช่องโหว่เที่ยงคืน–เช้า
 * ที่บทความของ "วันนี้" ยังไม่ถูกสร้าง ทำให้ลูกค้าที่ทักตอนดึกไม่มีดวงรายวันให้ส่ง)
 * $schedule->command('horoscope:generate-daily')->dailyAt('00:01');
 */
class HoroscopeGenerateDailyCommand extends Command
{
    /**
     * ชื่อและ arguments ของคำสั่ง
     *
     * @var string
     */
    protected $signature = 'horoscope:generate-daily
        {--date= : ระบุวันที่เป้าหมาย (Y-m-d) default=วันนี้}
        {--force : บังคับสร้างใหม่ แม้มีอยู่แล้ว}';

    /**
     * คำอธิบายคำสั่ง
     *
     * @var string
     */
    protected $description = 'สร้างดวงรายวัน 7+1 วันเกิด ด้วย AI อัตโนมัติ';

    /**
     * ดำเนินการหลัก
     *
     * @param HoroscopeDailyService $dailyService
     * @return int
     */
    public function handle(HoroscopeDailyService $dailyService): int
    {
        $dateStr = $this->option('date');

        // กำหนดวันที่เป้าหมาย
        try {
            $targetDate = $dateStr
                ? Carbon::parse($dateStr)
                : Carbon::now('Asia/Bangkok')->startOfDay();
        } catch (\Exception $e) {
            $this->error("รูปแบบวันที่ไม่ถูกต้อง: {$dateStr}");

            return self::FAILURE;
        }

        $thaiDate = $targetDate->locale('th')->translatedFormat('l j F').' '.($targetDate->year + 543);
        $this->info("🔮 เริ่มสร้างดวงรายวัน — {$thaiDate}");
        $this->newLine();

        $totalResults = ['success' => 0, 'failed' => 0, 'skipped' => 0];

        // สร้างดวง 7+1 วันเกิด
        $this->info('📅 สร้างดวง 7+1 วันเกิด...');
        $startTime = microtime(true);

        $birthdayResults = $dailyService->generateDailyForAllBirthDays($targetDate);

        $duration = round(microtime(true) - $startTime, 1);

        $this->table(
            ['ผลลัพธ์', 'จำนวน'],
            [
                ['✅ สำเร็จ', $birthdayResults['success']],
                ['❌ ล้มเหลว', $birthdayResults['failed']],
                ['⏭️ ข้าม (มีอยู่แล้ว)', $birthdayResults['skipped']],
                ['⏱️ เวลา', "{$duration} วินาที"],
            ]
        );

        $totalResults['success'] += $birthdayResults['success'];
        $totalResults['failed'] += $birthdayResults['failed'];
        $totalResults['skipped'] += $birthdayResults['skipped'];

        $this->newLine();

        // สรุปผล
        $this->info('📊 สรุปผลรวม:');
        $this->table(
            ['รายการ', 'จำนวน'],
            [
                ['✅ สร้างสำเร็จ', $totalResults['success']],
                ['❌ ล้มเหลว', $totalResults['failed']],
                ['⏭️ ข้ามแล้ว', $totalResults['skipped']],
                ['📝 รวมทั้งหมด', array_sum($totalResults)],
            ]
        );

        // แสดงผลสถานะ
        if ($totalResults['failed'] > 0) {
            $this->warn("⚠️ มีดวง {$totalResults['failed']} รายการที่สร้างไม่สำเร็จ ตรวจสอบ log ได้ที่ storage/logs/laravel.log");

            return self::FAILURE;
        }

        if ($totalResults['success'] > 0) {
            $this->info("✅ สร้างดวงรายวันสำเร็จทั้งหมด {$totalResults['success']} รายการ!");
        } else {
            $this->info('ℹ️ ดวงทั้งหมดถูกสร้างไว้แล้ว ไม่มีรายการใหม่');
        }

        return self::SUCCESS;
    }
}
