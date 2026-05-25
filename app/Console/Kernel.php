<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * ⚠️ (2026-05-25) Kernel::schedule() = DEAD CODE ใน Laravel 11
 *
 * Discovery: bootstrap/app.php ใช้ Application::configure() แต่ไม่ได้ register
 * Kernel ผ่าน ->withKernel(Kernel::class) → schedule() method นี้ไม่ถูกเรียก
 *
 * ผลกระทบที่เคยเกิด:
 *   - 30+ schedule entries ที่เคยอยู่ใน schedule() นี้ silent dead ตั้งแต่ L11 upgrade
 *   - fortune:check-pending / fortune:expire-stuck-paid / fortune:celtic-auto-finalize
 *     ไม่ทำงาน → ลูกค้าจ่ายแล้วไม่ได้คำทำนาย (กว่าจะรู้เพราะ user audit เช้า 2026-05-25)
 *
 * Fix (2026-05-25 commits: dd3a9a994 + this commit):
 *   - ย้าย schedules ทั้งหมดไป routes/console.php (Schedule:: facade)
 *   - Laravel 11 ใช้ routes/console.php auto-loaded ผ่าน bootstrap/app.php → withRouting(commands:)
 *
 * Class นี้ยังอยู่เพื่อ:
 *   - $this->load(__DIR__.'/Commands') — load command classes (ยังจำเป็น)
 *   - Backward compatibility ถ้ามี code อื่นอ้างถึง App\Console\Kernel
 *
 * 🚫 ห้ามเพิ่ม schedule entries ที่นี่ — จะไม่ทำงาน ใช้ routes/console.php
 */
class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * ⚠️ DEAD CODE — ไม่ถูกเรียกใน Laravel 11 (bootstrap/app.php ไม่มี withKernel)
     * ห้ามเพิ่ม schedule ที่นี่ — ใช้ routes/console.php แทน
     */
    protected function schedule(Schedule $schedule): void
    {
        // Intentionally empty — see class docblock for migration history
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
