<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง ai_core_usage_logs
     *
     * ตารางนี้เก็บ log การใช้งาน AI features
     * ใช้สำหรับติดตาม quota, analytics, และ billing
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_core_usage_logs')) {
            return;
        }

        Schema::create('ai_core_usage_logs', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('tenant_id')
                ->constrained('ai_core_tenants')
                ->onDelete('cascade')
                ->comment('Tenant ที่ใช้งาน');

            $table->foreignId('feature_id')
                ->constrained('ai_core_features')
                ->onDelete('cascade')
                ->comment('Feature ที่ถูกใช้งาน');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('ผู้ใช้งาน');

            // Usage information
            $table->string('action', 100)->comment('การกระทำ (generate, process, analyze, etc.)');
            $table->enum('status', ['success', 'failed', 'partial', 'pending'])->default('success')->index()->comment('สถานะการใช้งาน');

            // Resource tracking
            $table->integer('usage_amount')->default(1)->comment('จำนวนที่ใช้ (ขึ้นกับ quota_type)');
            $table->string('usage_unit', 50)->nullable()->comment('หน่วย (requests, tokens, minutes, etc.)');

            // Performance metrics
            $table->integer('response_time')->nullable()->comment('เวลาตอบสนอง (milliseconds)');
            $table->integer('tokens_used')->nullable()->comment('จำนวน tokens ที่ใช้ (สำหรับ LLM)');
            $table->decimal('cost', 10, 4)->nullable()->comment('ค่าใช้จ่าย (บาท)');

            // Request/Response data
            $table->text('request_data')->nullable()->comment('ข้อมูล request (อาจเก็บแบบสรุป)');
            $table->text('response_data')->nullable()->comment('ข้อมูล response (อาจเก็บแบบสรุป)');
            $table->text('error_message')->nullable()->comment('ข้อความ error (ถ้ามี)');

            // Metadata
            $table->string('ip_address', 45)->nullable()->comment('IP address ของผู้ใช้');
            $table->string('user_agent')->nullable()->comment('User agent');
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติม');

            // Timestamps (ไม่ใช้ updated_at เพราะเป็น log)
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index(['tenant_id', 'created_at']);
            $table->index(['feature_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('created_at'); // สำหรับ cleanup old logs
        });
    }

    /**
     * ลบตาราง ai_core_usage_logs
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_core_usage_logs');
    }
};
