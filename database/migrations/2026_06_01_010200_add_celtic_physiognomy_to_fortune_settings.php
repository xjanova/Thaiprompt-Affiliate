<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 👤 (2026-06-01) เพิ่ม enable_celtic_physiognomy toggle
     *
     * เปิด/ปิดการ inject ตำราโหงวเฮ้ง/ลักษณะคน ประจำไพ่ (จากคลัง RAG) เข้า prompt Celtic 99
     *   เมื่อลูกค้าถาม "เขาเป็นคนยังไง / หน้าตา-นิสัย / เนื้อคู่ / คู่กรณีเป็นใคร"
     *
     * default = true. admin ปิดได้ผ่าน:
     *   UPDATE fortune_telling_settings SET enable_celtic_physiognomy = 0
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_celtic_physiognomy')) {
                $table->boolean('enable_celtic_physiognomy')
                    ->default(true)
                    ->after('enable_celtic_mu_knowledge');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'enable_celtic_physiognomy')) {
                $table->dropColumn('enable_celtic_physiognomy');
            }
        });
    }
};
