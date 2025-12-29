<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * แก้ไขคอลัมน์ configuration จาก JSON เป็น TEXT
 *
 * เนื่องจาก EmailProvider model ใช้การ encrypt ค่า configuration
 * ทำให้ค่าที่เก็บเป็น base64 string ไม่ใช่ valid JSON
 * MySQL 8.0+ จะ reject ค่าที่ไม่ใช่ valid JSON ในคอลัมน์ประเภท JSON
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่ก่อน
        if (! Schema::hasTable('email_providers')) {
            return;
        }

        Schema::table('email_providers', function (Blueprint $table) {
            // เปลี่ยนจาก JSON เป็น TEXT เพื่อรองรับ encrypted data
            $table->text('configuration')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('email_providers')) {
            return;
        }

        // WARNING: การ rollback จะใช้งานได้เฉพาะถ้าข้อมูลใน configuration เป็น valid JSON
        // ถ้าข้อมูลเป็น encrypted string จะ fail
        Schema::table('email_providers', function (Blueprint $table) {
            $table->json('configuration')->change();
        });
    }
};
