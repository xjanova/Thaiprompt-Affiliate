<?php

namespace App\Console\Commands;

use App\Services\TelegramAlertService;
use Illuminate\Console\Command;

/**
 * 📣 ส่งข้อความแจ้งเตือนแอดมินทาง Telegram จาก shell script
 *
 * ทำไมมี (2026-11-08): สคริปต์สำรองทั้งเซิร์ฟเวอร์ (deployment/server-backup/server-backup-r2.sh)
 * ต้องเตือนเมื่อสำรองล้ม — บอทแจ้งเตือนตั้งไว้ในหลังบ้าน และ token เก็บเข้ารหัสใน DB
 * ⇒ ให้สคริปต์เรียกผ่านคำสั่งนี้ แทนการคัดลอก token ออกมาไว้ในไฟล์บนเครื่อง
 *
 * ไม่ใช้ LINE push — โควตา 300/เดือนสงวนให้ลูกค้าที่จ่ายเงิน (ดู TelegramAlertService)
 *
 * @example php artisan alert:telegram "สำรองล้ม" --key=server-backup-r2-db --throttle=60
 */
class AlertTelegram extends Command
{
    protected $signature = 'alert:telegram
        {message : ข้อความ (plain text)}
        {--key= : คีย์กันรัว — ข้อความคีย์เดียวกันส่งได้ครั้งเดียวในช่วง --throttle}
        {--throttle=60 : นาทีที่กันส่งซ้ำ (ใช้คู่กับ --key)}';

    protected $description = 'ส่งข้อความแจ้งเตือนแอดมินทาง Telegram (บอทแจ้งเตือนในหลังบ้าน)';

    public function handle(TelegramAlertService $telegram): int
    {
        $message = trim((string) $this->argument('message'));
        if ($message === '') {
            $this->error('ข้อความว่าง');

            return self::INVALID;
        }

        if (! $telegram->isConfigured()) {
            $this->warn('ยังไม่ได้ตั้งบอทแจ้งเตือน Telegram (หลังบ้าน /admin/fortune/channels) — ไม่ได้ส่ง');

            return self::FAILURE;
        }

        $key = $this->option('key') ? (string) $this->option('key') : null;
        $sent = $telegram->send(mb_substr($message, 0, 3500), $key, max(1, (int) $this->option('throttle')));

        $this->line($sent ? '✅ ส่งแล้ว' : 'ไม่ได้ส่ง (กันรัว หรือ Telegram ไม่ตอบ)');

        return $sent ? self::SUCCESS : self::FAILURE;
    }
}
