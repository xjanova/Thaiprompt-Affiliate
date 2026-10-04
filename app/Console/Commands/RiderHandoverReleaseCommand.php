<?php

namespace App\Console\Commands;

use App\Services\Rider\HandoverService;
use Illuminate\Console\Command;

/**
 * ปลดเงินอัตโนมัติของงานที่ไรเดอร์วางของไว้ (ไรเดอร์รอบ 2 — ทุกนาที)
 *
 * เงื่อนไข: การส่งมอบสถานะ fallback_pending_release + เลย auto_release_at + ผู้ซื้อไม่ร้องเรียน
 * → งาน awaiting_release → completed · การส่งมอบ released (method fallback)
 * → แบ่งเงินร้าน/จ่ายไรเดอร์/เงินคืน ตามปกติ (ตรวจซ้ำหลังล็อกทุกแถว รันซ้ำ/พร้อมกันได้ ไม่จ่ายซ้ำ)
 *
 * Usage: php artisan rider:handover-release [--limit=100]
 */
class RiderHandoverReleaseCommand extends Command
{
    protected $signature = 'rider:handover-release {--limit=100 : จำนวนงานสูงสุดต่อรอบ}';

    protected $description = 'ปลดเงินงานไรเดอร์ที่วางของไว้ครบเวลาแล้วและผู้ซื้อไม่ร้องเรียน';

    public function handle(HandoverService $handovers): int
    {
        $released = $handovers->releaseDue(max(1, (int) $this->option('limit')));

        $this->info("released={$released}");

        return self::SUCCESS;
    }
}
