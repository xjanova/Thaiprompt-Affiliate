<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์ "คำเตือนตอนบิลหมดเวลาเอง" (auto-expire) — แยกจาก cancel เพื่อให้โทนนุ่มกว่า
 *
 *   - fortune_consent_expire_enabled  เปิด/ปิดเตือนตอนบิลหมดเวลาเอง (default true)
 *   - fortune_consent_expire_text     ข้อความเตือนตอนหมดเวลา (โทนนุ่ม — แอดมินแก้)
 *
 * user: "ปล่อยให้หมดเวลาเอง → โทนนุ่มกว่า + แยกรูปต่างหาก"
 * (รูปแยกผ่าน usage_scope='expire' ในตาราง fortune_consent_images)
 *
 * ⚠️ ALTER TABLE — ใช้ Schema::hasColumn() เช็คทีละคอลัมน์ (CLAUDE.md กฎทอง #2)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_consent_expire_enabled')) {
                $table->boolean('fortune_consent_expire_enabled')->default(true);
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_consent_expire_text')) {
                $table->text('fortune_consent_expire_text')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach (['fortune_consent_expire_enabled', 'fortune_consent_expire_text'] as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
