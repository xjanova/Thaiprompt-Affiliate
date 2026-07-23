<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มค่า 'clawback' เข้า enum status ของตาราง mlm_commissions
     *
     * เหตุผล: MlmCommissionClawbackService อัพเดท status='clawback' ตอน refund
     * แต่ enum เดิมมีแค่ pending/approved/rejected/paid/cancelled
     * → บน MySQL strict mode จะ throw ทำให้ clawback ล้มเหลวทั้ง transaction
     */
    public function up(): void
    {
        // ทำเฉพาะเมื่อตารางมีอยู่ (กัน fresh install ที่ยังไม่มีตาราง)
        if (! Schema::hasTable('mlm_commissions')) {
            return;
        }

        // เช็คก่อนว่า enum มี 'clawback' แล้วหรือยัง (idempotent)
        $column = DB::selectOne("SHOW COLUMNS FROM `mlm_commissions` WHERE Field = 'status'");
        if ($column && str_contains($column->Type ?? '', 'clawback')) {
            return;
        }

        DB::statement("ALTER TABLE `mlm_commissions` MODIFY COLUMN `status` ENUM('pending','approved','rejected','paid','cancelled','clawback') NOT NULL DEFAULT 'pending'");
    }

    /**
     * ย้อนกลับ: ตัด 'clawback' ออกจาก enum (แปลงแถวที่เป็น clawback เป็น cancelled ก่อน)
     */
    public function down(): void
    {
        if (! Schema::hasTable('mlm_commissions')) {
            return;
        }

        // แปลงข้อมูลก่อนหด enum เพื่อไม่ให้ ALTER ล้มเหลว
        DB::table('mlm_commissions')->where('status', 'clawback')->update(['status' => 'cancelled']);

        DB::statement("ALTER TABLE `mlm_commissions` MODIFY COLUMN `status` ENUM('pending','approved','rejected','paid','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
