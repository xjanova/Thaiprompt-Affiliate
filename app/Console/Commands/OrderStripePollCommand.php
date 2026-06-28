<?php

namespace App\Console\Commands;

use App\Services\Payment\OrderStripeService;
use Illuminate\Console\Command;

/**
 * 💳 ตรวจ Stripe Checkout Session ของออเดอร์ที่ค้าง pending
 *
 * ตัวสำรองของ success_url return + webhook — กันกรณีลูกค้าจ่ายบัตรเสร็จแล้วปิดแท็บ
 * (ไม่ได้ redirect กลับ) + webhook ตก → ออเดอร์ค้าง pending ทั้งที่จ่ายเงินแล้ว
 */
class OrderStripePollCommand extends Command
{
    protected $signature = 'order:stripe-poll {--minutes=180 : ย้อนหลังกี่นาทีที่จะตรวจ}';

    protected $description = 'ตรวจ Stripe session ของออเดอร์ที่ค้างจ่าย แล้ว complete ให้อัตโนมัติ';

    public function handle(OrderStripeService $stripe): int
    {
        if (! $stripe->isEnabled()) {
            $this->info('Stripe (order) ยังไม่ได้เปิดใช้งาน — ข้าม');

            return self::SUCCESS;
        }

        $minutes = (int) $this->option('minutes');
        $completed = $stripe->pollPendingSessions($minutes);

        $this->info("order:stripe-poll เสร็จ — complete {$completed} ออเดอร์");

        return self::SUCCESS;
    }
}
