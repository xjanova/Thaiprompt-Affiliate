<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🎓 (2026-10-13) ใส่คอร์สห้องเรียนตั้งต้น 3 บทบาท + คลังข้อสอบ บน prod
 *
 * ⚠️ deploy.sh รัน migrate แต่ไม่รัน db:seed (ฐานที่มีข้อมูลแล้ว) → ต้องมี migration นี้
 *    เรียก ClassroomSeeder ซึ่ง idempotent: คอร์สที่มีอยู่แล้วไม่ทับ · คลังข้อสอบเติมเฉพาะคอร์สที่ยังว่าง
 *    ไม่ใส่บทเรียน (แอดมินวางลิงก์ YouTube เองที่ /admin/classroom)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('classroom_courses') || ! Schema::hasTable('classroom_questions')) {
            return;
        }

        try {
            (new \Database\Seeders\ClassroomSeeder)->run();
        } catch (\Throwable $e) {
            Log::warning('Seed ห้องเรียนใน migration ล้มเหลว (non-blocking) — รัน php artisan db:seed --class=ClassroomSeeder เองได้', [
                'error' => class_basename($e),
            ]);
        }
    }

    public function down(): void
    {
        // ไม่ลบข้อมูล (กันลบคอร์ส/ข้อสอบที่แอดมินแก้แล้ว) — ตารางถูกลบโดย migration สร้างตารางอยู่แล้ว
    }
};
