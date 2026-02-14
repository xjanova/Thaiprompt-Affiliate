<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * แก้ไขประเภทคอลัมน์ extracted_data ให้รองรับข้อมูล JSON ขนาดใหญ่
 *
 * ปัญหา: คอลัมน์ extracted_data อาจถูกสร้างเป็น VARCHAR
 * ทำให้เกิด error "Data too long for column" เมื่อ insert ข้อมูล JSON
 *
 * แก้ไข: เปลี่ยนเป็น LONGTEXT เพื่อรองรับ JSON ขนาดใหญ่
 */
return new class extends Migration
{
    /**
     * แก้ไขคอลัมน์ extracted_data เป็น LONGTEXT
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง kyc_verifications มีอยู่
        if (! Schema::hasTable('kyc_verifications')) {
            return;
        }

        // ตรวจสอบว่าคอลัมน์ extracted_data มีอยู่
        if (! Schema::hasColumn('kyc_verifications', 'extracted_data')) {
            // ถ้ายังไม่มี ให้สร้างใหม่เป็น LONGTEXT
            Schema::table('kyc_verifications', function (Blueprint $table) {
                $table->longText('extracted_data')->nullable()->after('submitted_at');
            });

            return;
        }

        // ถ้ามีอยู่แล้ว ให้แก้ไขประเภทเป็น LONGTEXT
        // ใช้ raw SQL เพื่อเปลี่ยนประเภทคอลัมน์โดยไม่สูญเสียข้อมูล
        DB::statement('ALTER TABLE kyc_verifications MODIFY COLUMN extracted_data LONGTEXT NULL');
    }

    /**
     * ย้อนกลับ (เปลี่ยนเป็น JSON)
     */
    public function down(): void
    {
        if (! Schema::hasTable('kyc_verifications')) {
            return;
        }

        if (Schema::hasColumn('kyc_verifications', 'extracted_data')) {
            // เปลี่ยนกลับเป็น JSON (ระวัง: อาจสูญเสียข้อมูลถ้าข้อมูลไม่ใช่ valid JSON)
            DB::statement('ALTER TABLE kyc_verifications MODIFY COLUMN extracted_data JSON NULL');
        }
    }
};
