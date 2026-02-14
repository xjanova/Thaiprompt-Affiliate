<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * แก้ไขคอลัมน์ conversation_state ให้รองรับข้อมูลขนาดใหญ่
 *
 * ปัญหา: SQLSTATE[22001] Data too long for column 'conversation_state'
 * เมื่อเก็บคำถามภาษาไทย 2+ ข้อใน JSON จะเกินขนาดคอลัมน์
 * แก้ไข: เปลี่ยนจาก json เป็น longText เพื่อรองรับข้อมูลขนาดใหญ่
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('fortune_readings', 'conversation_state')) {
            // ใช้ raw SQL เพื่อเปลี่ยน column type โดยไม่สูญเสียข้อมูล
            DB::statement('ALTER TABLE fortune_readings MODIFY conversation_state LONGTEXT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('fortune_readings', 'conversation_state')) {
            DB::statement('ALTER TABLE fortune_readings MODIFY conversation_state JSON NULL');
        }
    }
};
