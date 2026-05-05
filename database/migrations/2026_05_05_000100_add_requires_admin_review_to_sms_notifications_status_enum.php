<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * 🩹 (2026-05-05) เพิ่มค่า 'requires_admin_review' ใน enum status ของ sms_payment_notifications
 *
 * Bug ที่พบจาก production logs (2026-05-05):
 *   SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status' at row 1
 *   (Connection: mysql, SQL: update sms_payment_notifications set status = requires_admin_review)
 *
 * Root cause: SmsPaymentService::flagOrphanFortunePayment() (line 548) set status='requires_admin_review'
 *             แต่ enum เดิมรองรับแค่ ['pending', 'matched', 'confirmed', 'rejected', 'expired']
 *
 * Impact: SMS ที่ไม่ match บิล (เช่น ลูกค้าโอนเงินผิดยอด หรือ UPA หมดอายุ) → SQL exception →
 *         status ค้าง 'pending' → admin ไม่ได้รับ FCM alert → ลูกค้าโอนแล้วเงินค้าง
 *
 * Fix: ใช้ raw SQL ALTER COLUMN ขยาย enum + รองรับ rollback
 */
return new class extends Migration
{
    /**
     * รัน migration เพิ่ม value ใน enum
     */
    public function up(): void
    {
        if (! Schema::hasTable('sms_payment_notifications')) {
            Log::warning('Migration skip: sms_payment_notifications table not found');
            return;
        }

        // เช็ค driver — รองรับเฉพาะ MySQL/MariaDB (ENUM syntax)
        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            Log::warning('Migration skip: ENUM modify รองรับเฉพาะ MySQL/MariaDB', [
                'driver' => $driver,
            ]);
            return;
        }

        // เช็คว่า enum มี value 'requires_admin_review' แล้วหรือยัง
        try {
            $columnInfo = DB::selectOne("
                SELECT COLUMN_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'sms_payment_notifications'
                  AND COLUMN_NAME = 'status'
            ");

            if ($columnInfo && str_contains($columnInfo->COLUMN_TYPE, "'requires_admin_review'")) {
                Log::info('Migration skip: requires_admin_review มีอยู่ใน enum แล้ว');
                return;
            }
        } catch (\Throwable $e) {
            // ถ้า query inspect ล้มเหลว → ดำเนินการต่อ (ALTER ก็ idempotent ในเชิงตรรกะ)
            Log::warning('Migration: inspect column type ล้มเหลว — ดำเนินการ ALTER ต่อ', [
                'error' => $e->getMessage(),
            ]);
        }

        // ALTER ENUM — เพิ่ม 'requires_admin_review' + กัน edge case อื่น (cancelled, processing)
        DB::statement("
            ALTER TABLE sms_payment_notifications
            MODIFY COLUMN status ENUM(
                'pending',
                'matched',
                'confirmed',
                'rejected',
                'expired',
                'cancelled',
                'processing',
                'requires_admin_review'
            ) NOT NULL DEFAULT 'pending'
        ");

        Log::info('✅ Migration: เพิ่ม requires_admin_review + เพิ่ม cancelled/processing ใน enum status');
    }

    /**
     * Rollback — ลบ value ใหม่ ถ้าไม่มี row ใช้
     */
    public function down(): void
    {
        if (! Schema::hasTable('sms_payment_notifications')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        // ตรวจว่ามี row ที่ใช้ value ใหม่ — ถ้ามี ห้าม rollback (ป้องกัน data loss)
        $usedNewValues = DB::table('sms_payment_notifications')
            ->whereIn('status', ['requires_admin_review', 'cancelled', 'processing'])
            ->exists();

        if ($usedNewValues) {
            Log::warning('Migration rollback skip: มี row ใช้ value ใหม่อยู่ — ห้าม rollback');
            return;
        }

        DB::statement("
            ALTER TABLE sms_payment_notifications
            MODIFY COLUMN status ENUM(
                'pending',
                'matched',
                'confirmed',
                'rejected',
                'expired'
            ) NOT NULL DEFAULT 'pending'
        ");
    }
};
