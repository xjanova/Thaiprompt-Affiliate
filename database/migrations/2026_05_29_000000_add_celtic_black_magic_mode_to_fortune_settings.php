<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🪬 (2026-05-29) เพิ่ม enable_celtic_black_magic_mode toggle
     *
     * โหมดคุณไสย์/มนต์ดำ/ทำของ ใน Celtic 99 — หัวข้อพิเศษล็อกทั้งรอบ (เปิดได้เฉพาะคำถามแรก)
     * default = true (เปิด). admin ปิดได้ผ่าน UPDATE fortune_telling_settings SET enable_celtic_black_magic_mode = 0
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_celtic_black_magic_mode')) {
                $table->boolean('enable_celtic_black_magic_mode')
                    ->default(true)
                    ->after('enable_celtic_enrichment');
            }
        });
    }

    /**
     * ลบ column
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'enable_celtic_black_magic_mode')) {
                $table->dropColumn('enable_celtic_black_magic_mode');
            }
        });
    }
};
