<?php

namespace App\Console\Commands;

use App\Services\Media\ProfilePhotoService;
use Illuminate\Console\Command;

/**
 * ลบแคชรูปโปรไฟล์พร้อมลายน้ำของวันเก่า (ทุกวัน) — ไรเดอร์รอบ 2, 2026-10-04
 *
 * แคชเก็บเป็นโฟลเดอร์ต่อวัน storage/app/profile-photo-cache/{Ymd}/ (วันไทย)
 * ลายน้ำมีวันที่อยู่ในรูป แคชของวันก่อนจึงไม่ถูกใช้อีก — เก็บเมื่อวานไว้เผื่อช่วงข้ามเที่ยงคืน
 *
 * Usage: php artisan profile-photo:purge-cache [--keep-days=1]
 */
class ProfilePhotoPurgeCacheCommand extends Command
{
    protected $signature = 'profile-photo:purge-cache {--keep-days=1 : เก็บแคชย้อนหลังกี่วัน (0 = เหลือแค่วันนี้)}';

    protected $description = 'ลบแคชรูปโปรไฟล์พร้อมลายน้ำของวันเก่า';

    public function handle(ProfilePhotoService $photos): int
    {
        $keep = max(0, (int) $this->option('keep-days'));
        $deleted = $photos->purgeRenderCache($keep);

        $this->info("purged_days={$deleted} keep_days={$keep}");

        return self::SUCCESS;
    }
}
