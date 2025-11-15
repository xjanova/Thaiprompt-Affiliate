<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง google_translate_settings สำหรับ Arrow X Theme System
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ CRITICAL: ตรวจสอบตารางก่อนสร้างเสมอ
        if (Schema::hasTable('google_translate_settings')) {
            return;
        }

        Schema::create('google_translate_settings', function (Blueprint $table) {
            $table->id();

            // API Credentials
            $table->string('api_key')->comment('Google Cloud API Key (encrypted)');
            $table->string('project_id', 100)->nullable();

            // Default Settings
            $table->string('default_source_language', 5)->default('th')->comment('ภาษาต้นทาง');
            $table->json('enabled_target_languages')->default(json_encode(['en', 'ja', 'zh', 'ko']))->comment('ภาษาที่เปิดใช้งาน');

            // Features
            $table->boolean('auto_detect_enabled')->default(true)->comment('ตรวจจับภาษาอัตโนมัติ');
            $table->boolean('cache_enabled')->default(true)->comment('ใช้ cache');
            $table->integer('cache_ttl')->default(2592000)->comment('Cache TTL (seconds) = 30 days');

            // Limits & Quotas
            $table->integer('daily_quota')->default(10000)->comment('จำนวนคำแปลสูงสุดต่อวัน');
            $table->integer('monthly_quota')->default(300000);
            $table->integer('current_daily_usage')->default(0);
            $table->integer('current_monthly_usage')->default(0);
            $table->timestamp('quota_reset_at')->nullable();

            // Fallback
            $table->string('fallback_language', 5)->default('th')->comment('ภาษาสำรอง');
            $table->boolean('fallback_enabled')->default(true);

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_api_call_at')->nullable();
            $table->text('last_error_message')->nullable();

            // Created/Updated
            $table->timestamps();

            // Indexes
            $table->index('is_active');
        });
    }

    /**
     * ลบตาราง google_translate_settings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('google_translate_settings');
    }
};
