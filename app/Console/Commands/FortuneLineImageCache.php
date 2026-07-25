<?php

namespace App\Console\Commands;

use App\Models\TarotCard;
use App\Services\LineFortuneService;
use Illuminate\Console\Command;

/**
 * แปลงรูปไพ่ WebP → JPEG ล่วงหน้า สำหรับส่งบน LINE
 *
 * 🖼️ (2026-07-25) LINE ไม่รองรับ WebP (รูปไพ่ทั้ง 78 ใบเป็น .webp)
 *   → ลูกค้า LINE ไม่เห็นหน้าไพ่เลย ส่วน Facebook เห็นปกติ
 *   LineFortuneService::lineSafeImageUrl() แปลงให้อัตโนมัติตอนใช้งานจริงอยู่แล้ว
 *   command นี้ warm cache ล่วงหน้า — ลูกค้าคนแรกของแต่ละใบไม่ต้องรอแปลง
 *
 * ใช้: php artisan fortune:line-image-cache
 */
class FortuneLineImageCache extends Command
{
    protected $signature = 'fortune:line-image-cache';

    protected $description = 'แปลงรูปไพ่ WebP เป็น JPEG ล่วงหน้า เพื่อให้ LINE แสดงรูปไพ่ได้';

    /**
     * รันการแปลงรูปไพ่ทั้งหมด
     */
    public function handle(): int
    {
        $lineService = app(LineFortuneService::class);

        $cards = TarotCard::whereNotNull('image_url')->get();
        if ($cards->isEmpty()) {
            $this->warn('ไม่พบไพ่ในระบบ');

            return self::SUCCESS;
        }

        $this->info("🃏 กำลังเตรียมรูปไพ่ {$cards->count()} ใบ สำหรับ LINE...");

        $converted = 0;
        $skipped = 0;
        foreach ($cards as $card) {
            $original = (string) $card->image_url;
            $safe = $lineService->lineSafeImageUrl($original);

            if ($safe !== $original && str_ends_with($safe, '.jpg')) {
                $converted++;
                $this->line("  ✅ {$card->getName('th')}");
            } else {
                $skipped++;
                $this->line("  ➖ {$card->getName('th')} (ไม่ต้องแปลง หรือแปลงไม่ได้)");
            }
        }

        $this->newLine();
        $this->info("✨ เสร็จสิ้น — แปลง/พร้อมใช้ {$converted} ใบ, ข้าม {$skipped} ใบ");

        return self::SUCCESS;
    }
}
