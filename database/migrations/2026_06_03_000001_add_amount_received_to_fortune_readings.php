<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ amount_received ในตาราง fortune_readings
     *
     * เก็บ "ยอดที่รับจริง" (จาก SlipOK / SMS / admin force) แยกจาก amount_paid
     * ที่เป็น "ยอดบิลที่ตั้งไว้" (unique amount ~99.74 ใช้ match + คิดค่าคอม)
     *
     * เหตุผลแยกคอลัมน์: amount_paid ถูกใช้เป็น "ราคาบิล" ในหลายจุด (commission, matching)
     * → overwrite ด้วยยอดจริง (เช่น 179) จะทำให้ค่าคอม/รายงานเพี้ยน
     * จึงเก็บยอดจริงในคอลัมน์ใหม่ แสดงในหน้า admin ว่ารับมาเท่าไรจริง
     *
     * ⚠️ ALTER TABLE — ใช้ Schema::hasColumn() เช็ค (ห้าม hasTable + return)
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_readings')) {
            return;
        }

        Schema::table('fortune_readings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_readings', 'amount_received')) {
                $table->decimal('amount_received', 12, 2)->nullable()->after('amount_paid')
                    ->comment('ยอดที่รับจริง (SlipOK/SMS/admin) — แยกจาก amount_paid ที่เป็นยอดบิลตั้งไว้');
            }
        });
    }

    /**
     * ลบคอลัมน์ amount_received
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_readings')) {
            return;
        }

        Schema::table('fortune_readings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_readings', 'amount_received')) {
                $table->dropColumn('amount_received');
            }
        });
    }
};
