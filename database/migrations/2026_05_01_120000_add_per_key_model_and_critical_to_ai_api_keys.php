<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์ใน ai_api_keys สำหรับ:
 * 1. Per-key model selection (`model`) — แต่ละ key ใช้ model ต่างกันได้
 * 2. Custom base URL (`base_url`) — สำหรับ provider ที่มี endpoint ต่างกัน (Xiaomi, OpenRouter, custom proxy)
 * 3. Critical state (`is_critical`) — มาร์คเป็น critical หลังลองเช็คซ้ำ 3 ครั้งไม่สำเร็จ → ไม่ใช้อัตโนมัติ
 * 4. Health-check tracking (`error_check_attempts`, `last_health_check_at`)
 *    → ติดตามจำนวนครั้งที่ "ปลดปล่อย disabled แล้วลองใหม่" ก่อนจะ critical
 */
return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์อย่างปลอดภัย — เช็คก่อนทุกคอลัมน์
     */
    public function up(): void
    {
        Schema::table('ai_api_keys', function (Blueprint $table) {
            // 🎯 Per-key model selection (override default)
            $this->safeAddColumn($table, 'ai_api_keys', 'model', function ($table) {
                $table->string('model', 100)->nullable()->after('provider')
                    ->comment('โมเดลที่ใช้สำหรับ key นี้ (override default ของ provider)');
            });

            // 🌐 Custom base URL (สำหรับ provider ที่มี endpoint ต่าง)
            $this->safeAddColumn($table, 'ai_api_keys', 'base_url', function ($table) {
                $table->string('base_url', 255)->nullable()->after('model')
                    ->comment('Custom API base URL (override default — สำหรับ Xiaomi, custom proxy ฯลฯ)');
            });

            // 🔴 Critical state — 3 strikes ไม่สำเร็จ → ไม่ใช้อัตโนมัติ รอ admin ลบ
            $this->safeAddColumn($table, 'ai_api_keys', 'is_critical', function ($table) {
                $table->boolean('is_critical')->default(false)->after('is_active')
                    ->comment('🔴 critical (3 strikes failed health check) — ไม่ใช้อัตโนมัติ รอ admin ตรวจสอบ');
            });

            // 🩺 Health-check tracking
            $this->safeAddColumn($table, 'ai_api_keys', 'error_check_attempts', function ($table) {
                $table->unsignedTinyInteger('error_check_attempts')->default(0)->after('consecutive_errors')
                    ->comment('จำนวนครั้งที่ลอง re-check หลัง disabled — 3 = critical');
            });

            $this->safeAddColumn($table, 'ai_api_keys', 'last_health_check_at', function ($table) {
                $table->timestamp('last_health_check_at')->nullable()->after('error_check_attempts')
                    ->comment('เวลา health check ล่าสุด');
            });
        });

        // 📊 Index สำหรับ query critical keys
        $this->safeAddIndex('ai_api_keys', 'is_critical');
    }

    /**
     * ลบคอลัมน์ที่เพิ่ม
     */
    public function down(): void
    {
        $this->safeDropIndex('ai_api_keys', 'ai_api_keys_is_critical_index');
        $this->safeDropColumn('ai_api_keys', [
            'model',
            'base_url',
            'is_critical',
            'error_check_attempts',
            'last_health_check_at',
        ]);
    }
};
