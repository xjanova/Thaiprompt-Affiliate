<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มระบบกัน "คอมเมนต์รัวในโพสต์เดียว"
     *
     * เจ้าของกำหนด (2026-08-09): ใครคอมเมนต์เกิน 5 ครั้งในโพสต์เดียว = ถือว่าสแปม
     * → แบนไว้ก่อน แล้วเข้าหน้าจัดการเดียวกับคนโพสต์ลิงก์ พร้อมระบุว่าผิดข้อไหน
     *
     * ⚠️ ALTER TABLE — ห้ามใช้ Schema::hasTable() + return
     */
    public function up(): void
    {
        Schema::table('fortune_comment_link_blocks', function (Blueprint $table) {
            // แยกประเภทความผิด — หน้าจัดการเดียวกันแต่ต้องรู้ว่าโดนเพราะอะไร
            if (! Schema::hasColumn('fortune_comment_link_blocks', 'violation_type')) {
                $table->string('violation_type', 20)
                    ->default('link')
                    ->after('platform')
                    ->comment('link = แปะลิงก์ / flood = คอมเมนต์รัวเกินกำหนดในโพสต์เดียว');
            }

            // เก็บจำนวนครั้งที่คอมในโพสต์นั้น ตอนที่เข้าเกณฑ์ (ใช้เป็นหลักฐาน)
            if (! Schema::hasColumn('fortune_comment_link_blocks', 'flood_count')) {
                $table->unsignedInteger('flood_count')
                    ->nullable()
                    ->after('matched_domain')
                    ->comment('จำนวนคอมเมนต์ในโพสต์เดียวกัน ณ ตอนที่ถูกจับ');
            }
        });

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'comment_flood_enabled')) {
                $table->boolean('comment_flood_enabled')
                    ->default(true)
                    ->comment('เปิดระบบจับคอมเมนต์รัวในโพสต์เดียว');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'comment_flood_threshold')) {
                $table->unsignedSmallInteger('comment_flood_threshold')
                    ->default(5)
                    ->comment('คอมเมนต์ในโพสต์เดียวเกินกี่ครั้งถึงถือว่าสแปม (เกินค่านี้ = โดน)');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('fortune_comment_link_blocks', function (Blueprint $table) {
            foreach (['violation_type', 'flood_count'] as $col) {
                if (Schema::hasColumn('fortune_comment_link_blocks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach (['comment_flood_enabled', 'comment_flood_threshold'] as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
