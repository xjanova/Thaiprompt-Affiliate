<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ซ่อม mobile_banners.position ที่ค้างเป็น enum('top','middle','bottom') บน production
     *
     * ทำไมต้องมี:
     *   migration 2025_12_21_000001 ตั้งใจเปลี่ยน position เป็น varchar(20) (home/shop/...)
     *   แต่บรรทัดแรกของมัน UPDATE position = 'home' ใส่ค่าที่ enum เดิมไม่มี → ล้มบน prod (MariaDB strict)
     *   แล้ว Smart Migration ของ deploy บันทึกว่า "รันแล้ว" ทั้งที่ล้ม ⇒ คอลัมน์ค้างเป็น enum เก่า
     *   ผลต่อเนื่อง (deploy v4.0.521): seed แบนเนอร์เปิดตัว 2026_09_26_133100 ล้มด้วย
     *   "Data truncated for column 'position'" และถูกบันทึกว่ารันแล้วเหมือนกัน → แอปไม่มีแบนเนอร์เปิดตัว
     *   และแอดมินสร้างแบนเนอร์ตำแหน่ง home/taladsod/rider/merchant ไม่ได้
     *
     * ทำอะไร:
     *   1. ถ้า position ยังเป็น enum → เปลี่ยนเป็น VARCHAR(20) NOT NULL DEFAULT 'home'
     *      แล้วแปลงค่าเดิม top/middle/bottom → home ตามเจตนาของ migration ธ.ค. (ให้ตรงกับเครื่อง dev/CI)
     *      ฐานที่เป็น varchar อยู่แล้ว (dev, CI) ข้ามขั้นนี้ทั้งหมด
     *   2. รัน seed แบนเนอร์เปิดตัวซ้ำ — idempotent (ข้ามแถวที่มี campaign_key แล้ว ไม่ทับที่แอดมินแก้)
     */
    public function up(): void
    {
        if (! Schema::hasTable('mobile_banners') || ! Schema::hasColumn('mobile_banners', 'position')) {
            return;
        }

        if (strtolower((string) Schema::getColumnType('mobile_banners', 'position')) === 'enum') {
            // MODIFY จาก enum เป็น varchar เก็บค่าเดิมเป็นสตริงตามเดิม (ใช้ได้ทั้ง MySQL 8 และ MariaDB 10.6)
            DB::statement("ALTER TABLE `mobile_banners` MODIFY `position` VARCHAR(20) NOT NULL DEFAULT 'home'");

            DB::table('mobile_banners')
                ->whereIn('position', ['top', 'middle', 'bottom'])
                ->update(['position' => 'home']);
        }

        (require __DIR__.'/2026_09_26_133100_seed_launch_app_campaign_banners.php')->up();
    }

    /**
     * ไม่ย้อนกลับเป็น enum เก่า — ค่า home/taladsod/rider/merchant ใส่ enum เดิมไม่ได้ (ข้อมูลจะหาย)
     */
    public function down(): void
    {
        //
    }
};
