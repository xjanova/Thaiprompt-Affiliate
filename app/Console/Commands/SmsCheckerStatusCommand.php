<?php

namespace App\Console\Commands;

use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * แสดงสถานะระบบ SMS Checker Payment
 *
 * แสดงข้อมูลสรุป: จำนวน devices, notifications, matching stats
 */
class SmsCheckerStatusCommand extends Command
{
    /**
     * ชื่อคำสั่งและ arguments
     *
     * @var string
     */
    protected $signature = 'smschecker:status';

    /**
     * คำอธิบายคำสั่ง
     *
     * @var string
     */
    protected $description = 'แสดงสถานะระบบ SMS Checker Payment (devices, notifications, matching)';

    /**
     * รันคำสั่ง
     */
    public function handle(): int
    {
        $this->info('📊 สถานะระบบ SMS Checker Payment');
        $this->newLine();

        // สถานะ Devices
        $this->info('📱 Devices:');
        $this->table(
            ['สถานะ', 'จำนวน'],
            [
                ['Active', SmsCheckerDevice::where('status', 'active')->count()],
                ['Inactive', SmsCheckerDevice::where('status', 'inactive')->count()],
                ['Blocked', SmsCheckerDevice::where('status', 'blocked')->count()],
                ['รวม', SmsCheckerDevice::count()],
            ]
        );

        // สถานะ Notifications
        $this->info('📨 Notifications:');
        $this->table(
            ['สถานะ', 'จำนวน'],
            [
                ['Pending', SmsPaymentNotification::where('status', 'pending')->count()],
                ['Matched', SmsPaymentNotification::where('status', 'matched')->count()],
                ['Confirmed', SmsPaymentNotification::where('status', 'confirmed')->count()],
                ['Rejected', SmsPaymentNotification::where('status', 'rejected')->count()],
                ['Expired', SmsPaymentNotification::where('status', 'expired')->count()],
                ['รวม', SmsPaymentNotification::count()],
            ]
        );

        // สถานะ Unique Amounts
        $this->info('💰 Unique Payment Amounts:');
        $this->table(
            ['สถานะ', 'จำนวน'],
            [
                ['Reserved (กำลังใช้งาน)', UniquePaymentAmount::where('status', 'reserved')
                    ->where('expires_at', '>', now())->count()],
                ['Used (จับคู่แล้ว)', UniquePaymentAmount::where('status', 'used')->count()],
                ['Expired', UniquePaymentAmount::where('status', 'expired')->count()],
                ['Cancelled', UniquePaymentAmount::where('status', 'cancelled')->count()],
                ['รวม', UniquePaymentAmount::count()],
            ]
        );

        // Nonces
        $nonceCount = DB::table('sms_payment_nonces')->count();
        $this->info("🔑 Nonces ที่เก็บอยู่: {$nonceCount}");

        // แสดง devices ที่ active ล่าสุด
        $this->newLine();
        $this->info('📱 อุปกรณ์ที่ active ล่าสุด:');
        $recentDevices = SmsCheckerDevice::where('status', 'active')
            ->orderByDesc('last_active_at')
            ->limit(5)
            ->get(['device_id', 'device_name', 'last_active_at', 'ip_address']);

        if ($recentDevices->isEmpty()) {
            $this->warn('  ไม่มีอุปกรณ์ที่ active');
        } else {
            $this->table(
                ['Device ID', 'ชื่อ', 'Active ล่าสุด', 'IP'],
                $recentDevices->map(fn ($d) => [
                    $d->device_id,
                    $d->device_name ?? '-',
                    $d->last_active_at?->diffForHumans() ?? 'ไม่เคย',
                    $d->ip_address ?? '-',
                ])->toArray()
            );
        }

        return Command::SUCCESS;
    }
}
