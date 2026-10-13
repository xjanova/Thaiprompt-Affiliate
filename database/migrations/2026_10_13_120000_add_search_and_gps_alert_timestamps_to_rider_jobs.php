<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⏱️ กติกาตัดเวลาตลาดสด/ไรเดอร์ (2026-10-13)
 *
 * - search_started_at   : เวลาที่ "รอบหาไรเดอร์ปัจจุบัน" เริ่ม (สร้างงาน หรือกลับเข้าคิวครั้งล่าสุดหลังไรเดอร์คืนงาน/GPS หาย/ถูกระงับ)
 *                         ใช้จับเวลา ส่งแอดมิน / ผู้ซื้อยกเลิกได้ (30 นาที) / ระบบยกเลิกคืนเงิน (60 นาที) — เดิมนับจาก created_at
 *                         งานเก่าที่ยังว่าง = ใช้เวลาคืนงานล่าสุดใน dispatch_attempts หรือ created_at แทน (RiderJob::searchStartedAt)
 * - gps_lost_alerted_at : แจ้งแอดมิน+ผู้ซื้อเรื่อง GPS ไรเดอร์หายหลังรับของแล้ว (ครั้งเดียวต่อเหตุการณ์ — ล้างเมื่อ GPS กลับมา)
 *
 * ⚠️ ใช้ dateTime ไม่ใช้ timestamp — MariaDB บน prod เติม ON UPDATE ให้ timestamp คอลัมน์แรกเอง (แก้แถวแล้วเวลาเปลี่ยน)
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        Schema::table('rider_jobs', function (Blueprint $table) {
            $this->safeAddColumn($table, 'rider_jobs', 'search_started_at', fn ($t) => $t->dateTime('search_started_at')->nullable());
            $this->safeAddColumn($table, 'rider_jobs', 'gps_lost_alerted_at', fn ($t) => $t->dateTime('gps_lost_alerted_at')->nullable());
        });
    }

    public function down(): void
    {
        $this->safeDropColumn('rider_jobs', ['search_started_at', 'gps_lost_alerted_at']);
    }
};
