<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🎯 (2026-05-02) เพิ่ม column `purpose` ใน ai_api_keys
 *
 * User request: "ตั้งค่า key ว่าเฉพาะการทำนายเท่านั้น ไม่ใช้สำหรับ chat"
 *
 * Values:
 *   - 'any' (default) — ใช้ได้ทุกอย่าง (prediction + chat + อื่นๆ)
 *   - 'prediction'    — เฉพาะคำทำนาย deep readings (กัน chat ใช้แล้วเปลือง quota)
 *   - 'chat'          — เฉพาะ chat conversation (กัน prediction ดูดหมด)
 *
 * Filter logic: getAllAvailableKeys($purpose) → WHERE purpose IN ('any', $purpose)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_api_keys') || Schema::hasColumn('ai_api_keys', 'purpose')) {
            return;
        }

        Schema::table('ai_api_keys', function (Blueprint $table) {
            $table->enum('purpose', ['any', 'prediction', 'chat'])
                ->default('any')
                ->after('priority')
                ->comment('any=ทุกอย่าง / prediction=เฉพาะคำทำนาย / chat=เฉพาะแชท');
            $table->index('purpose', 'ai_api_keys_purpose_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_api_keys') || ! Schema::hasColumn('ai_api_keys', 'purpose')) {
            return;
        }

        Schema::table('ai_api_keys', function (Blueprint $table) {
            $table->dropIndex('ai_api_keys_purpose_idx');
            $table->dropColumn('purpose');
        });
    }
};
