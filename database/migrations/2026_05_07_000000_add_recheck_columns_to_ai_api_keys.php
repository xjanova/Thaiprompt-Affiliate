<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🩺 (2026-05-07) เพิ่มคอลัมน์ใน ai_api_keys สำหรับ Auto-Recheck Banned Keys
 *
 * Background:
 *   ปัจจุบัน key ที่โดน 3-strikes critical จะถูก lock ถาวรจนกว่า admin จะกด clearCritical
 *   แต่หลายครั้ง key พังชั่วคราว (provider outage, network glitch, model rename) แล้วกลับมาใช้ได้
 *   → admin ต้องเข้ามากดเอง = ลำบาก + คีย์ดีๆ ถูก idle
 *
 * Solution:
 *   Scheduled command ai:recheck-critical รันทุกชั่วโมง:
 *     1. Query keys ที่ is_critical=true + ถึงเวลา recheck (next_recheck_at <= now)
 *     2. Probe minimal API call (เช่น list models)
 *     3. ถ้า probe success → unban อัตโนมัติ + log auto_recovered_at + แจ้ง admin
 *     4. ถ้า probe fail → keep banned + อัปเดต next_recheck_at ตาม exponential backoff (1h → 4h → 12h → 24h → 72h)
 *
 * Columns:
 *   - last_recheck_at         : เวลา probe ครั้งล่าสุด (rate limit guard)
 *   - recheck_failure_count   : นับจำนวน probe failure ติด → backoff scaling
 *   - next_recheck_at         : เวลา probe ครั้งถัดไป (cron filter)
 *   - auto_recovered_at       : timestamp ที่ recover (สำหรับ analytics + admin alert)
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        Schema::table('ai_api_keys', function (Blueprint $table) {
            // 🩺 เวลา probe ครั้งล่าสุด — กัน probe ซ้ำเร็วเกิน
            $this->safeAddColumn($table, 'ai_api_keys', 'last_recheck_at', function ($table) {
                $table->timestamp('last_recheck_at')->nullable()->after('last_health_check_at')
                    ->comment('🩺 (2026-05-07) เวลา auto-recheck ครั้งล่าสุด');
            });

            // 🔁 จำนวน probe failure ติดต่อกัน → exponential backoff
            $this->safeAddColumn($table, 'ai_api_keys', 'recheck_failure_count', function ($table) {
                $table->unsignedSmallInteger('recheck_failure_count')->default(0)->after('last_recheck_at')
                    ->comment('🔁 (2026-05-07) จำนวน probe failure ติด — drives exponential backoff');
            });

            // ⏰ เวลา probe ครั้งถัดไป (cron filter — query เฉพาะ key ที่ <= now())
            $this->safeAddColumn($table, 'ai_api_keys', 'next_recheck_at', function ($table) {
                $table->timestamp('next_recheck_at')->nullable()->after('recheck_failure_count')
                    ->comment('⏰ (2026-05-07) เวลา recheck ครั้งถัดไป — cron filter');
            });

            // 🟢 timestamp ที่ auto-recover (analytics + admin notification trigger)
            $this->safeAddColumn($table, 'ai_api_keys', 'auto_recovered_at', function ($table) {
                $table->timestamp('auto_recovered_at')->nullable()->after('next_recheck_at')
                    ->comment('🟢 (2026-05-07) เวลา key auto-recover (สำหรับ admin alert + analytics)');
            });
        });

        // 📊 Index สำหรับ query "keys ที่ critical + ถึงเวลา recheck"
        $this->safeAddIndex('ai_api_keys', 'next_recheck_at');
    }

    public function down(): void
    {
        $this->safeDropIndex('ai_api_keys', 'ai_api_keys_next_recheck_at_index');
        $this->safeDropColumn('ai_api_keys', [
            'last_recheck_at',
            'recheck_failure_count',
            'next_recheck_at',
            'auto_recovered_at',
        ]);
    }
};
