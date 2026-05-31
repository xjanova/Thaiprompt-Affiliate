<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🩺 (2026-06-01) เพิ่ม enable_celtic_health_tome toggle
     *
     * ตำราสุขภาพประจำไพ่ (config/fortune_tarot_health.php) ใน Celtic 99 —
     *   เมื่อลูกค้าถามเรื่องสุขภาพ แม่หมอจะดึง "อวัยวะ/โรค/อาการ/ความรุนแรง" ประจำไพ่
     *   ที่เปิดได้ทั้ง 10 ใบ มาทำนายให้เฉพาะเจาะจง-แม่นยำ-หลากหลายขึ้น
     *
     * default = true (เปิด). admin ปิดได้ผ่าน:
     *   UPDATE fortune_telling_settings SET enable_celtic_health_tome = 0
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_celtic_health_tome')) {
                $table->boolean('enable_celtic_health_tome')
                    ->default(true)
                    ->after('enable_celtic_black_magic_mode');
            }
        });
    }

    /**
     * ลบ column
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'enable_celtic_health_tome')) {
                $table->dropColumn('enable_celtic_health_tome');
            }
        });
    }
};
