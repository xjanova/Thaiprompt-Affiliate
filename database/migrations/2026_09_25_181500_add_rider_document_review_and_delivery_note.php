<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ฟิลด์เสริมของ API/หลังบ้านไรเดอร์ (2026-09-25)
 *
 * riders.documents_changed_at
 *   ไรเดอร์ที่อนุมัติแล้วเปลี่ยนเอกสารสำคัญ (บัตร/ใบขับขี่/ทะเบียนรถ) → แอดมินต้องตรวจซ้ำ
 *   (audit RIDER-27) แอดมินกด "ตรวจแล้ว" หรืออนุมัติใหม่ = ล้างค่า
 *
 * rider_jobs.delivery_note
 *   หมายเหตุจากไรเดอร์ตอนส่งของสำเร็จ (เช่น "ฝากไว้กับ รปภ.") — แยกจาก delivery_notes
 *   ซึ่งเป็นคำสั่งของผู้ซื้อ ห้ามเขียนทับกัน
 *
 * prod ตอนนี้ riders = 0, rider_jobs = 0 แถว → เพิ่มคอลัมน์ได้ปลอดภัย
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $this->safeAddColumn($table, 'riders', 'documents_changed_at', function ($table) {
                $table->timestamp('documents_changed_at')->nullable()->after('profile_image')
                    ->comment('เอกสารถูกเปลี่ยนหลังอนุมัติ → รอแอดมินตรวจซ้ำ');
            });
        });

        Schema::table('rider_jobs', function (Blueprint $table) {
            $this->safeAddColumn($table, 'rider_jobs', 'delivery_note', function ($table) {
                $table->text('delivery_note')->nullable()->after('delivery_proof_image')
                    ->comment('หมายเหตุจากไรเดอร์ตอนส่งของสำเร็จ');
            });
        });
    }

    public function down(): void
    {
        $this->safeDropColumn('riders', 'documents_changed_at');
        $this->safeDropColumn('rider_jobs', 'delivery_note');
    }
};
