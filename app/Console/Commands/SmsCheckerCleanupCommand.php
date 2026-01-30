<?php

namespace App\Console\Commands;

use App\Services\SmsPaymentService;
use Illuminate\Console\Command;

/**
 * ทำความสะอาดข้อมูล SMS Payment ที่หมดอายุ
 *
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
    protected $signature = 'smschecker:cleanup';

    /**
     * คำอธิบายคำสั่ง
     *
     * @var string
     */
    protected $description = 'ทำความสะอาดข้อมูล SMS Payment ที่หมดอายุ (nonces, unique amounts, notifications)';

    /**
     * รันคำสั่ง
     *
     * @return int
     */
    public function handle(SmsPaymentService $service): int
    {
        $this->info('🧹 กำลังทำความสะอาดข้อมูล SMS Payment...');

        $service->cleanup();

        $this->info('✅ ทำความสะอาดข้อมูล SMS Payment สำเร็จ!');

        return Command::SUCCESS;
    }
}
