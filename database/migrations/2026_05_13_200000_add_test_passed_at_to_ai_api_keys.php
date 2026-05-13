<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🛡️ (2026-05-13) Health gate — key ต้อง "เทสผ่าน" ก่อนระบบจะเลือก
 *
 * User spec:
 *   "ทุกคีย์ต้องเทสผ่านมาแล้วจึงจะนำมาลงสนามในการเลือก"
 *
 * Fields ใหม่:
 *   - last_test_passed_at  : datetime — เวลา test สำเร็จล่าสุด (null = ยังไม่เคยผ่าน)
 *   - last_test_failed_at  : datetime — เวลา test fail ล่าสุด (track for retry)
 *   - last_test_message    : string  — error message ล่าสุด (debug)
 *
 * Backfill:
 *   - keys ที่ available อยู่แล้ว → set last_test_passed_at = now() (assume working)
 *   - keys ที่ disabled → ปล่อย null (ต้อง re-test ก่อน enable)
 *
 * Usage:
 *   - AiApiKey::available() จะ filter last_test_passed_at IS NOT NULL
 *   - admin คลิก "ทดสอบ" → ถ้า pass set timestamp → key พร้อมใช้
 *   - หรือ admin add key → controller auto-test → set timestamp ทันที
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (! Schema::hasTable('ai_api_keys')) {
            return;
        }

        Schema::table('ai_api_keys', function (Blueprint $table) {
            $this->safeAddColumn($table, 'ai_api_keys', 'last_test_passed_at', function ($table) {
                $table->timestamp('last_test_passed_at')->nullable()->after('last_health_check_at');
            });

            $this->safeAddColumn($table, 'ai_api_keys', 'last_test_failed_at', function ($table) {
                $table->timestamp('last_test_failed_at')->nullable()->after('last_test_passed_at');
            });

            $this->safeAddColumn($table, 'ai_api_keys', 'last_test_message', function ($table) {
                $table->string('last_test_message', 500)->nullable()->after('last_test_failed_at');
            });
        });

        $this->safeAddIndex('ai_api_keys', 'last_test_passed_at');

        // 🛡️ Backfill — keys ที่ active+verified อยู่แล้ว → ถือว่า passed
        //   เหตุผล: ไม่ต้องการ break ระบบ production ทันทีหลัง deploy
        //   admin มี time มาทดสอบ key ใหม่ๆ ที่เพิ่ม
        try {
            \DB::table('ai_api_keys')
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('is_critical')->orWhere('is_critical', false);
                })
                ->whereNull('last_test_passed_at')
                ->update(['last_test_passed_at' => now()]);
        } catch (\Throwable $e) {
            // Backfill fail ไม่ critical — admin ต้องคลิก test เอง
            \Log::warning('Migration: backfill last_test_passed_at fail', ['error' => $e->getMessage()]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_api_keys')) {
            return;
        }

        $this->safeDropIndex('ai_api_keys', 'ai_api_keys_last_test_passed_at_index');
        $this->safeDropColumn('ai_api_keys', ['last_test_passed_at', 'last_test_failed_at', 'last_test_message']);
    }
};
