<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 💰 (2026-06-05, user) รองรับ "โอนขาด → ทยอยเติมจนครบ" สำหรับบิลดูดวง (Celtic 99 / Deep 39)
 *
 * เคส: ลูกค้าโอนไม่ครบ (เช่น 39 จาก 99) + ส่งสลิป → SlipOK อ่านได้ 39 → reject_amount
 *   ระบบต้อง: จำยอดที่รับแล้ว, บอกยอดที่ขาด, สร้างบิล top-up (ยอดที่ขาด) ผูก reading เดิม,
 *   วนจนครบ 99. ครบ 3 รอบยังไม่ครบ = พักเงินไว้ให้แอดมิน/แม่หมอตรวจ (HOLD 1 ชม.)
 *
 * ⚠️ เพิ่มคอลัมน์ — ห้ามใช้ Schema::hasTable()+return (เช็คทีละคอลัมน์แทน)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_readings')) {
            return;
        }

        Schema::table('fortune_readings', function (Blueprint $table) {
            // ยอดสะสมที่รับมาแล้วจริง (เครดิต) — เช่น 39 → 69 → ...
            if (! Schema::hasColumn('fortune_readings', 'partial_paid_total')) {
                $table->decimal('partial_paid_total', 10, 2)->default(0)->after('amount_paid');
            }
            // ยอดเป้าหมายเต็ม (จับครั้งแรกที่โอนขาด เช่น 99) — UPA base จะหดเป็น 60/30 จึงต้องจำเป้าแยก
            if (! Schema::hasColumn('fortune_readings', 'partial_target_total')) {
                $table->decimal('partial_target_total', 10, 2)->nullable()->after('partial_paid_total');
            }
            // จำนวนครั้งที่โอนขาด (3 = ถือว่าก่อกวน → HOLD)
            if (! Schema::hasColumn('fortune_readings', 'partial_rounds')) {
                $table->unsignedTinyInteger('partial_rounds')->default(0)->after('partial_target_total');
            }
            // transRef ทุกใบที่เครดิตไปแล้ว (กันสลิปเดิมมาเครดิตซ้ำ)
            if (! Schema::hasColumn('fortune_readings', 'partial_transrefs')) {
                $table->json('partial_transrefs')->nullable()->after('partial_rounds');
            }
            // เวลาเริ่ม HOLD (พักรอแอดมิน/แม่หมอตรวจ) — null = ไม่ได้พัก
            if (! Schema::hasColumn('fortune_readings', 'partial_hold_at')) {
                $table->timestamp('partial_hold_at')->nullable()->after('partial_transrefs');
                $table->index('partial_hold_at', 'fr_partial_hold_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_readings')) {
            return;
        }

        Schema::table('fortune_readings', function (Blueprint $table) {
            foreach (['partial_paid_total', 'partial_target_total', 'partial_rounds', 'partial_transrefs', 'partial_hold_at'] as $col) {
                if (Schema::hasColumn('fortune_readings', $col)) {
                    if ($col === 'partial_hold_at') {
                        try {
                            $table->dropIndex('fr_partial_hold_idx');
                        } catch (\Throwable $e) {
                            // index อาจไม่มี — ข้าม
                        }
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
