<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🌟 (2026-05-08) เพิ่ม sensitive_ai_pool_key_id ให้ admin เลือก specific key
 *
 * เดิม: generateSensitiveChatResponse acquireKey(provider, 'sensitive')
 *       — ดึง key purpose='sensitive' ตัวแรกที่เจอ (ไม่ control ได้)
 * ใหม่: ถ้า admin set sensitive_ai_pool_key_id → ใช้ key นั้นเสมอ
 *       (lock ไว้ key ตัวเดียว, predictable cost + behavior)
 *
 * ON DELETE SET NULL — ถ้า key ถูกลบ → settings reset เป็น null
 *                      → caller fallback ไป pool acquireKey เดิม
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'sensitive_ai_pool_key_id')) {
                $table->unsignedBigInteger('sensitive_ai_pool_key_id')
                    ->nullable()
                    ->after('sensitive_model')
                    ->comment('Lock specific ai_api_keys.id สำหรับ Sensitive AI (null=ใช้ pool rotation)');

                // FK constraint — set null on delete (ไม่ break settings ถ้า key หาย)
                if (Schema::hasTable('ai_api_keys')) {
                    try {
                        $table->foreign('sensitive_ai_pool_key_id', 'fk_settings_sensitive_key')
                            ->references('id')
                            ->on('ai_api_keys')
                            ->onDelete('set null');
                    } catch (\Throwable $e) {
                        // FK อาจ fail ถ้า MySQL strict mode + existing row — ignore (constraint optional)
                    }
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            try {
                $table->dropForeign('fk_settings_sensitive_key');
            } catch (\Throwable $e) {
                // FK อาจไม่มี — ignore
            }
            if (Schema::hasColumn('fortune_telling_settings', 'sensitive_ai_pool_key_id')) {
                $table->dropColumn('sensitive_ai_pool_key_id');
            }
        });
    }
};
