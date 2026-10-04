<?php

namespace App\Console\Commands;

use App\Services\Ekyc\EkycService;
use Illuminate\Console\Command;

/**
 * 🪪 ลบรอบยืนยันตัวตน eKYC ที่ผู้ใช้ทำค้างแล้วหมดอายุ (แถว + รูปบัตรที่เข้ารหัสไว้) — ทุกวัน
 *
 * ข้อมูลชีวภาพเก็บเท่าที่จำเป็น (PDPA): รอบที่ไม่ได้ส่งใบหน้าจนหมดเวลา ไม่มีประโยชน์ให้เก็บต่อ
 * + ลบรูป (เก็บแถวไว้) ของรอบที่ให้ถ่ายใหม่/ถูกแทนที่ (30 วัน) และที่แอดมินปฏิเสธ (180 วัน)
 * รอบที่อนุมัติ/รอตรวจไม่ถูกแตะ — ลบพร้อมบัญชีเท่านั้น
 *
 * Usage: php artisan ekyc:purge-stale
 */
class EkycPurgeStaleCommand extends Command
{
    protected $signature = 'ekyc:purge-stale';

    protected $description = 'ลบรอบยืนยันตัวตน eKYC ที่ทำค้างและหมดอายุแล้ว (แถว + รูปเข้ารหัส)';

    public function handle(EkycService $ekyc): int
    {
        $purged = $ekyc->purgeStaleSessions();
        $images = $ekyc->purgeExpiredImages();

        $this->info("purged_sessions={$purged} purged_images={$images}");

        return self::SUCCESS;
    }
}
