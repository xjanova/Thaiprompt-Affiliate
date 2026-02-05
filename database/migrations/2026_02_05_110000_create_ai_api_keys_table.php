<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง ai_api_keys สำหรับเก็บ API Keys ของ AI Providers
 *
 * รองรับ:
 * - หลาย providers (grok, openai, gemini, groq, qwen, openrouter)
 * - Pool management (วนใช้หลาย keys)
 * - Token usage tracking
 * - Rate limit monitoring
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_api_keys')) {
            return;
        }

        Schema::create('ai_api_keys', function (Blueprint $table) {
            $table->id();

            // ข้อมูลพื้นฐาน
            $table->string('name', 100)->comment('ชื่อ key สำหรับแสดงผล');
            $table->string('provider', 50)->index()->comment('ผู้ให้บริการ: grok, openai, gemini, groq, qwen, openrouter');
            $table->text('api_key')->comment('API Key (encrypted)');

            // สถานะ
            $table->boolean('is_active')->default(true)->index()->comment('เปิด/ปิดใช้งาน');
            $table->integer('priority')->default(0)->comment('ลำดับความสำคัญ (สูง = ใช้ก่อน)');

            // Token tracking
            $table->bigInteger('tokens_used_today')->default(0)->comment('Tokens ที่ใช้ไปวันนี้');
            $table->bigInteger('tokens_used_month')->default(0)->comment('Tokens ที่ใช้ไปเดือนนี้');
            $table->bigInteger('tokens_used_total')->default(0)->comment('Tokens ที่ใช้ไปทั้งหมด');
            $table->bigInteger('tokens_limit_daily')->nullable()->comment('จำกัด tokens/วัน (null = ไม่จำกัด)');
            $table->bigInteger('tokens_limit_monthly')->nullable()->comment('จำกัด tokens/เดือน (null = ไม่จำกัด)');

            // Rate limit tracking
            $table->integer('requests_today')->default(0)->comment('จำนวน requests วันนี้');
            $table->integer('requests_minute')->default(0)->comment('จำนวน requests นาทีปัจจุบัน');
            $table->integer('rate_limit_per_minute')->nullable()->comment('จำกัด requests/นาที');
            $table->timestamp('last_rate_limit_reset')->nullable()->comment('เวลา reset rate limit ล่าสุด');

            // สถานะการใช้งาน
            $table->timestamp('last_used_at')->nullable()->comment('ใช้งานล่าสุด');
            $table->integer('consecutive_errors')->default(0)->comment('จำนวน errors ติดต่อกัน');
            $table->text('last_error')->nullable()->comment('Error ล่าสุด');
            $table->timestamp('last_error_at')->nullable()->comment('เวลา error ล่าสุด');
            $table->timestamp('disabled_until')->nullable()->comment('ปิดใช้งานชั่วคราวจนถึง');

            // ข้อมูลเพิ่มเติม
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติม (model preferences, etc.)');
            $table->text('notes')->nullable()->comment('หมายเหตุ');

            // Timestamps
            $table->date('tokens_reset_date')->nullable()->comment('วันที่ reset tokens ล่าสุด');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['provider', 'is_active'], 'ai_keys_provider_active_idx');
            $table->index(['is_active', 'priority'], 'ai_keys_active_priority_idx');
        });

        // สร้างตาราง ai_api_key_usage_logs สำหรับเก็บ log การใช้งาน
        if (!Schema::hasTable('ai_api_key_usage_logs')) {
            Schema::create('ai_api_key_usage_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_api_key_id')->constrained('ai_api_keys')->onDelete('cascade');
                $table->string('model', 100)->nullable()->comment('Model ที่ใช้');
                $table->integer('input_tokens')->default(0);
                $table->integer('output_tokens')->default(0);
                $table->integer('total_tokens')->default(0);
                $table->integer('response_time_ms')->nullable()->comment('เวลาตอบกลับ (ms)');
                $table->boolean('is_success')->default(true);
                $table->text('error_message')->nullable();
                $table->string('request_type', 50)->nullable()->comment('ประเภท request: fortune, chat, etc.');
                $table->timestamps();

                $table->index(['ai_api_key_id', 'created_at'], 'ai_usage_key_date_idx');
                $table->index('created_at');
            });
        }

        // สร้างตาราง ai_api_key_settings สำหรับเก็บ settings ของ pool
        if (!Schema::hasTable('ai_api_key_settings')) {
            Schema::create('ai_api_key_settings', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 50)->unique()->comment('ผู้ให้บริการ');

                // โหมดการวนใช้
                $table->string('rotation_mode', 30)->default('round_robin')
                    ->comment('โหมดวนใช้: round_robin, least_used, priority, random, failover');

                // การตั้งค่า failover
                $table->integer('max_consecutive_errors')->default(3)->comment('จำนวน errors ก่อน disable ชั่วคราว');
                $table->integer('disable_duration_minutes')->default(5)->comment('ระยะเวลา disable (นาที)');
                $table->boolean('auto_disable_on_limit')->default(true)->comment('ปิดอัตโนมัติเมื่อถึง limit');

                // ค่าเริ่มต้น
                $table->string('default_model', 100)->nullable()->comment('Model เริ่มต้น');
                $table->integer('default_rate_limit')->nullable()->comment('Rate limit เริ่มต้น');

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_api_key_usage_logs');
        Schema::dropIfExists('ai_api_key_settings');
        Schema::dropIfExists('ai_api_keys');
    }
};
