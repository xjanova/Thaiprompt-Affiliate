<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ขยาย admin_notify_psid ให้เก็บได้หลายคน (คั่นด้วยจุลภาค)
     *
     * เดิม varchar(100) = ใส่ได้ ~5 PSID แล้วตัดทิ้งเงียบๆ ถ้าเกิน
     * (บทเรียนเดิม: คอลัมน์แคบกว่าที่คิด → ข้อมูลหายโดยไม่มีอะไรฟ้องบนจอ)
     */
    public function up(): void
    {
        if (! Schema::hasColumn('fortune_telling_settings', 'admin_notify_psid')) {
            return;
        }

        // ใช้ raw MODIFY แทน ->change() เพื่อคุมสเปกให้ชัดและไม่พึ่ง dbal
        DB::statement("ALTER TABLE `fortune_telling_settings`
            MODIFY `admin_notify_psid` VARCHAR(500) NULL
            COMMENT 'PSID แอดมินที่รับแจ้งเตือน — หลายคนคั่นด้วย , (comma)'");
    }

    /**
     * ย่อกลับ 100 ตามเดิม
     */
    public function down(): void
    {
        if (! Schema::hasColumn('fortune_telling_settings', 'admin_notify_psid')) {
            return;
        }

        DB::statement('ALTER TABLE `fortune_telling_settings`
            MODIFY `admin_notify_psid` VARCHAR(100) NULL');
    }
};
