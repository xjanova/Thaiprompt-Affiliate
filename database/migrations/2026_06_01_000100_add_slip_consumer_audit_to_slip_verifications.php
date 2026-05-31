<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🕵️ (2026-06-01) Slip Consumer Audit — กันสลิป "ของคนอื่น" (req #5, โหมด audit + เตือน ไม่ reject)
 *
 * บริบท:
 *   transRef dedup + บัญชีปลายทาง=ของร้าน กันได้เคสส่วนใหญ่ (สลิปซ้ำ / โอนผิดบัญชี)
 *   แต่ "สลิปจริง+ยังไม่เคยใช้+โอนเข้าบัญชีร้านวันนี้" ของลูกค้า A ที่คนอื่นเอามาส่ง — แยกไม่ออก
 *   เพราะลูกค้า FB/LINE ไม่ระบุชื่อบัญชีจริง การปิด 100% ต้องผูกชื่อผู้โอน = เสี่ยง reject ลูกค้าจริง
 *
 * ทางออก (user spec 2026-06-01): "audit log + เตือน ไม่ reject"
 *   - consumed_by_platform / consumed_by_user_id: บันทึกว่าใคร (chat user) ใช้สลิปใบนี้ → ตรวจสอบย้อนหลัง
 *   - flagged_review / flag_reason: ระบบ heuristic ตั้งธงเตือนเมื่อพบรูปแบบน่าสงสัย
 *       (ชื่อผู้โอนเดียวกันถูกใช้โดยลูกค้า chat หลายราย / ลูกค้า chat คนเดียวใช้สลิปหลายชื่อผู้โอน)
 *     → แจ้งแอดมิน + เปิดไพ่ให้ลูกค้าตามปกติ (ไม่ปฏิเสธ)
 *
 * ⚠️ ALTER TABLE เพิ่มคอลัมน์ — ใช้ hasColumn เช็คทีละตัว (ห้าม hasTable+return)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('slip_verifications')) {
            return; // ตารางหลักยังไม่ถูกสร้าง → migration ก่อนหน้าจะสร้างให้
        }

        Schema::table('slip_verifications', function (Blueprint $table) {
            if (! Schema::hasColumn('slip_verifications', 'consumed_by_platform')) {
                $table->string('consumed_by_platform', 16)->nullable()->after('fortune_reading_id')
                    ->comment('แพลตฟอร์มของลูกค้าที่ใช้สลิปใบนี้ (facebook/line) — audit');
            }
            if (! Schema::hasColumn('slip_verifications', 'consumed_by_user_id')) {
                $table->string('consumed_by_user_id', 128)->nullable()->after('consumed_by_platform')
                    ->comment('chat user id ของลูกค้าที่ใช้สลิปใบนี้ — audit/dispute');
            }
            if (! Schema::hasColumn('slip_verifications', 'flagged_review')) {
                $table->boolean('flagged_review')->default(false)->after('status')
                    ->comment('ตั้งธงเตือน (สลิปน่าสงสัยว่าอาจเป็นของคนอื่น) — ไม่ reject');
            }
            if (! Schema::hasColumn('slip_verifications', 'flag_reason')) {
                $table->string('flag_reason', 255)->nullable()->after('flagged_review')
                    ->comment('เหตุผลที่ตั้งธงเตือน (heuristic)');
            }
        });

        // index สำหรับ query audit — try/catch กัน "duplicate key name" ตอน re-run
        // (ไม่ใช้ doctrine/dbal เพราะ Laravel 11 อาจไม่ได้ติดตั้ง)
        foreach ([
            'consumed_by_user_id' => 'slip_verif_consumer_idx',
            'flagged_review' => 'slip_verif_flagged_idx',
        ] as $col => $idxName) {
            try {
                Schema::table('slip_verifications', function (Blueprint $table) use ($col, $idxName) {
                    $table->index($col, $idxName);
                });
            } catch (\Throwable $e) {
                // index มีอยู่แล้ว — ข้าม
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('slip_verifications')) {
            return;
        }

        Schema::table('slip_verifications', function (Blueprint $table) {
            foreach (['slip_verif_consumer_idx', 'slip_verif_flagged_idx'] as $idx) {
                try {
                    $table->dropIndex($idx);
                } catch (\Throwable $e) {
                    // index อาจไม่มี — ข้าม
                }
            }

            foreach (['consumed_by_platform', 'consumed_by_user_id', 'flagged_review', 'flag_reason'] as $c) {
                if (Schema::hasColumn('slip_verifications', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
