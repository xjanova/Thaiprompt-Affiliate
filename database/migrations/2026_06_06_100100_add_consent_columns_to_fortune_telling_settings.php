<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์ระบบ "กติกาก่อนจองคิว" (Consent Gate) ใน fortune_telling_settings
 *
 * 5 คอลัมน์:
 *   - fortune_consent_enabled        เปิด/ปิดกล่องกติกาก่อนสร้างบิล
 *   - fortune_consent_text           ข้อความกติกา (แอดมินแก้ — fallback เป็น const ใน model)
 *   - fortune_consent_pick_strategy  กลยุทธ์สุ่มรูป (random/rotation/sequential)
 *   - fortune_consent_cancel_enabled เปิด/ปิดการเตือนสติ + ส่งรูปตอนลูกค้ากดยกเลิกบิล
 *   - fortune_consent_cancel_text    ข้อความเตือนตอนยกเลิก (แอดมินแก้)
 *
 * ⚠️ ALTER TABLE — ห้ามใช้ Schema::hasTable() + return (คอลัมน์ใหม่จะไม่ถูกสร้าง!)
 *    ใช้ Schema::hasColumn() เช็คทีละคอลัมน์ (CLAUDE.md กฎทอง #2)
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ consent ใน fortune_telling_settings
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return; // กันพังถ้า base table ยังไม่มี (fresh install order)
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_consent_enabled')) {
                $table->boolean('fortune_consent_enabled')->default(true);
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_consent_pick_strategy')) {
                $table->string('fortune_consent_pick_strategy', 20)->default('random');
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_consent_text')) {
                $table->text('fortune_consent_text')->nullable();
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_consent_cancel_enabled')) {
                $table->boolean('fortune_consent_cancel_enabled')->default(true);
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_consent_cancel_text')) {
                $table->text('fortune_consent_cancel_text')->nullable();
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach ([
                'fortune_consent_enabled',
                'fortune_consent_pick_strategy',
                'fortune_consent_text',
                'fortune_consent_cancel_enabled',
                'fortune_consent_cancel_text',
            ] as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
