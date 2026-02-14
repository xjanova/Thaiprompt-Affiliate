<?php

namespace App\Console\Commands;

use App\Services\SmsPaymentService;
use Illuminate\Console\Command;

/**
 * ทำความสะอาดข้อมูล SMS Payment ที่หมดอายุ
 *
 * ทำงานอัตโนมัติทุก 5 นาที:
 * - ยกเลิก Orders/Transactions ที่หมดเวลาชำระ (30 นาที)
 * - ล้าง unique amounts ที่หมดอายุ
 * - ล้าง nonces เก่า
 * - ล้าง notifications ที่ pending นานเกินไป
 */
class SmsCheckerCleanupCommand extends Command
{
    /**
     * ชื่อคำสั่งและ arguments
     *
     * @var string
     */
    protected $signature = 'smschecker:cleanup
                            {--dry-run : แสดงจำนวนที่จะถูกลบโดยไม่ลบจริง}';

    /**
     * คำอธิบายคำสั่ง
     *
     * @var string
     */
    protected $description = 'ทำความสะอาดข้อมูล SMS Payment และยกเลิก Orders ที่หมดเวลาชำระ (30 นาที)';

    /**
     * รันคำสั่ง
     */
    public function handle(SmsPaymentService $service): int
    {
        $this->info('');
        $this->info('🧹 SMS Checker Cleanup');
        $this->info('======================');
        $this->info('');

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - ไม่มีข้อมูลถูกแก้ไข');
            $this->info('');

            // แสดงจำนวนที่จะถูก clean
            $expiredAmounts = \App\Models\UniquePaymentAmount::where('status', 'reserved')
                ->where('expires_at', '<=', now())
                ->count();

            $oldNonces = \Illuminate\Support\Facades\DB::table('sms_payment_nonces')
                ->where('used_at', '<', now()->subHours(config('smschecker.nonce_expiry_hours', 24)))
                ->count();

            $oldNotifications = \App\Models\SmsPaymentNotification::where('status', 'pending')
                ->where('created_at', '<', now()->subDays(7))
                ->count();

            $this->info("📊 จะยกเลิก {$expiredAmounts} unique amounts (และ orders/transactions ที่เกี่ยวข้อง)");
            $this->info("📊 จะลบ {$oldNonces} nonces เก่า");
            $this->info("📊 จะยกเลิก {$oldNotifications} notifications เก่า");

            return self::SUCCESS;
        }

        $stats = $service->cleanup();

        $this->table(['รายการ', 'จำนวน'], [
            ['ยกเลิก Orders หมดเวลา', $stats['cancelled_orders']],
            ['ยกเลิก Transactions หมดเวลา', $stats['cancelled_transactions']],
            ['Expired Unique Amounts', $stats['expired_amounts']],
            ['ลบ Nonces เก่า', $stats['deleted_nonces']],
            ['Expired Notifications', $stats['expired_notifications']],
        ]);

        $this->info('');
        $this->info('✅ ทำความสะอาดข้อมูล SMS Payment สำเร็จ!');

        return Command::SUCCESS;
    }
}
