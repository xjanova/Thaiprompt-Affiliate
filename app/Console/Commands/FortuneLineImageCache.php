<?php

namespace App\Console\Commands;

use App\Models\TarotCard;
use App\Services\LineFortuneService;
use Illuminate\Console\Command;

/**
 * เตรียมรูปที่ส่งเข้า LINE ให้รอดด่าน Hotlink Protection ของ Cloudflare
 *
 * 🖼️ (2026-09-19) เปลี่ยนทิศจากของเดิม
 *   เดิม (2026-07-25): แปลง WebP → JPEG เพราะเข้าใจว่า LINE ไม่รองรับ WebP
 *   ตอนนี้พิสูจน์แล้วว่า **LINE แสดง .webp ได้ทั้งในแอพและบนเว็บ OA** และตัวที่
 *   ทำให้รูปหายคือ Cloudflare Hotlink Protection ซึ่งกันเฉพาะ jpg/jpeg/png/gif/ico
 *   ⇒ กลับทิศเป็น "อะไรที่ไม่ใช่ .webp → แปลงเป็น .webp" (ดู LineFortuneService::lineSafeImageUrl)
 *
 *   command นี้ warm cache ล่วงหน้า — ลูกค้าคนแรกของแต่ละรูปไม่ต้องรอ GD แปลง
 *   (ใช้งานจริงแปลงอัตโนมัติอยู่แล้ว ไม่รันก็ได้)
 *
 * ใช้: php artisan fortune:line-image-cache
 */
class FortuneLineImageCache extends Command
{
    protected $signature = 'fortune:line-image-cache';

    protected $description = 'เตรียมรูป (ไพ่ + การ์ด) เป็น WebP ล่วงหน้า ให้รอดด่าน Hotlink ของ Cloudflare';

    /**
     * รูปนิ่งที่ส่งเข้าแชทบ่อย — เก็บใน public/ ไม่ได้อยู่ใน DB
     *
     * @var array<string>
     */
    private const STATIC_IMAGES = [
        'images/fortune/packages/pkg-deep39.jpg',
        'images/fortune/packages/pkg-celtic99.jpg',
        'images/fortune/packages/pkg-blackmagic99.jpg',
        'images/fortune/entry/entry-free-daily.jpg',
        'images/fortune/entry/entry-vip.jpg',
    ];

    /**
     * เตรียมรูปทั้งหมดที่ส่งเข้า LINE
     */
    public function handle(): int
    {
        $lineService = app(LineFortuneService::class);

        if (! config('line_media.webp_shield', true)) {
            $this->warn('⚠️ เกราะปิดอยู่ (LINE_WEBP_SHIELD=false) — ไม่มีอะไรต้องเตรียม');

            return self::SUCCESS;
        }

        $targets = [];

        foreach (TarotCard::whereNotNull('image_url')->get() as $card) {
            $targets[] = [
                'label' => $card->getName('th'),
                'url' => (string) $card->image_url,
            ];
        }

        // การ์ดวันเกิด 7 ใบ (day-0 ถึง day-6)
        $staticImages = self::STATIC_IMAGES;
        for ($day = 0; $day <= 6; $day++) {
            $staticImages[] = sprintf('images/fortune/days/day-%d.jpg', $day);
        }

        foreach ($staticImages as $relative) {
            $targets[] = [
                'label' => basename($relative),
                'url' => asset($relative),
            ];
        }

        if ($targets === []) {
            $this->warn('ไม่พบรูปที่ต้องเตรียม');

            return self::SUCCESS;
        }

        $this->info('🖼️ กำลังเตรียมรูป '.count($targets).' ไฟล์ สำหรับ LINE...');

        $shielded = 0;      // แปลงเป็น .webp แล้ว (เดิมเป็น jpg/png/gif)
        $alreadySafe = 0;   // เป็น .webp อยู่แล้ว — รอดด่านโดยไม่ต้องแปลง
        $failed = 0;        // แปลงไม่ได้ (ไฟล์หาย / GD อ่านไม่ออก) — ยังส่ง URL เดิมได้

        foreach ($targets as $target) {
            $original = $target['url'];
            $safe = $lineService->lineSafeImageUrl($original);

            if (str_contains($safe, '/storage/line-webp/')) {
                $shielded++;
                $this->line("  ✅ {$target['label']}");
            } elseif (preg_match('/\.webp(\?|$)/i', $safe)) {
                $alreadySafe++;
                $this->line("  ➖ {$target['label']} (เป็น .webp อยู่แล้ว)");
            } else {
                $failed++;
                $this->line("  ⚠️ {$target['label']} (แปลงไม่ได้ — ส่ง URL เดิม)");
            }
        }

        $this->newLine();
        $this->info("✨ เสร็จสิ้น — แปลงใหม่/พร้อมใช้ {$shielded} · เป็น .webp อยู่แล้ว {$alreadySafe} · แปลงไม่ได้ {$failed}");

        if ($failed > 0) {
            $this->warn('⚠️ รูปที่แปลงไม่ได้จะถูกส่งด้วย URL เดิม — บนเว็บ LINE OA อาจยังไม่ขึ้นจนกว่าจะแก้ Hotlink ที่ Cloudflare');
        }

        return self::SUCCESS;
    }
}
