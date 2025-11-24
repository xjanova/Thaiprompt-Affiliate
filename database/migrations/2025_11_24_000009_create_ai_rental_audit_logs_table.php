<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง ai_rental_audit_logs
     *
     * เก็บ audit trail ของทุกการกระทำในระบบ AI Rental
     * สำหรับ compliance, security และ debugging
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_rental_audit_logs')) {
            return;
        }

        Schema::create('ai_rental_audit_logs', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('ผู้ใช้ที่ทำ action (null = system)');

            $table->foreignId('deployment_id')
                ->nullable()
                ->constrained('ai_rental_deployments')
                ->onDelete('set null')
                ->comment('Deployment ที่เกี่ยวข้อง');

            $table->foreignId('cloud_config_id')
                ->nullable()
                ->constrained('ai_rental_cloud_configs')
                ->onDelete('set null')
                ->comment('Config ที่เกี่ยวข้อง');

            // Action Details
            $table->string('action')
                ->comment('การกระทำที่ทำ');
            // action: deployment.created, deployment.started, deployment.stopped,
            //         deployment.deleted, config.created, config.updated, config.deleted,
            //         budget.created, budget.exceeded, alert.triggered, health_check.failed,
            //         api.called, settings.changed, etc.

            $table->string('action_category')->nullable()
                ->comment('หมวดหมู่การกระทำ');
            // categories: deployment, configuration, budget, monitoring, security, api, settings

            $table->enum('action_type', ['create', 'read', 'update', 'delete', 'execute', 'access'])
                ->default('execute')
                ->comment('ประเภทการกระทำ (CRUD)');

            $table->string('resource_type')->nullable()
                ->comment('ประเภท resource');
            // resource_type: deployment, config, alert, budget, health_check, api_endpoint

            $table->string('resource_id')->nullable()
                ->comment('ID ของ resource');

            $table->string('resource_name')->nullable()
                ->comment('ชื่อ resource');

            // Request Details
            $table->text('description')
                ->comment('คำอธิบายการกระทำ');

            $table->json('changes_before')->nullable()
                ->comment('ข้อมูลก่อนเปลี่ยนแปลง (JSON)');

            $table->json('changes_after')->nullable()
                ->comment('ข้อมูลหลังเปลี่ยนแปลง (JSON)');

            $table->json('request_data')->nullable()
                ->comment('ข้อมูล request (JSON)');

            $table->json('response_data')->nullable()
                ->comment('ข้อมูล response (JSON)');

            // Result
            $table->enum('status', ['success', 'failed', 'pending', 'warning'])
                ->default('success')
                ->comment('ผลลัพธ์');

            $table->text('error_message')->nullable()
                ->comment('ข้อความ error (ถ้ามี)');

            $table->integer('http_status_code')->nullable()
                ->comment('HTTP status code');

            // User Context
            $table->string('ip_address', 45)->nullable()
                ->comment('IP address ของผู้ใช้');

            $table->text('user_agent')->nullable()
                ->comment('User agent string');

            $table->string('session_id')->nullable()
                ->comment('Session ID');

            $table->string('request_id')->nullable()
                ->comment('Request ID (for tracing)');

            // Location & Device
            $table->string('country_code', 2)->nullable()
                ->comment('รหัสประเทศ');

            $table->string('city')->nullable()
                ->comment('เมือง');

            $table->string('device_type')->nullable()
                ->comment('ประเภทอุปกรณ์ (desktop, mobile, tablet)');

            $table->string('browser')->nullable()
                ->comment('เบราว์เซอร์');

            $table->string('platform')->nullable()
                ->comment('ระบบปฏิบัติการ');

            // API & Method
            $table->string('http_method')->nullable()
                ->comment('HTTP method (GET, POST, PUT, DELETE)');

            $table->text('endpoint')->nullable()
                ->comment('API endpoint');

            $table->text('url')->nullable()
                ->comment('URL ที่เข้าถึง');

            $table->text('referrer')->nullable()
                ->comment('Referrer URL');

            // Performance
            $table->integer('duration_ms')->nullable()
                ->comment('ระยะเวลาที่ใช้ (ms)');

            $table->integer('memory_usage_mb')->nullable()
                ->comment('Memory ที่ใช้ (MB)');

            $table->integer('query_count')->nullable()
                ->comment('จำนวน database queries');

            // Cost Impact
            $table->decimal('cost_impact', 10, 2)->nullable()
                ->comment('ผลกระทบต่อค่าใช้จ่าย (USD)');

            $table->decimal('cost_before', 10, 2)->nullable()
                ->comment('ค่าใช้จ่ายก่อนเปลี่ยนแปลง');

            $table->decimal('cost_after', 10, 2)->nullable()
                ->comment('ค่าใช้จ่ายหลังเปลี่ยนแปลง');

            // Security & Compliance
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])
                ->default('low')
                ->comment('ระดับความสำคัญ');

            $table->boolean('is_sensitive')->default(false)
                ->comment('เป็นข้อมูลที่ sensitive');

            $table->boolean('requires_review')->default(false)
                ->comment('ต้องการการตรวจสอบ');

            $table->boolean('reviewed')->default(false)
                ->comment('ตรวจสอบแล้ว');

            $table->timestamp('reviewed_at')->nullable()
                ->comment('ตรวจสอบเมื่อ');

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('ตรวจสอบโดย');

            // Tags & Categorization
            $table->json('tags')->nullable()
                ->comment('Tags สำหรับจัดหมวดหมู่');

            $table->boolean('is_automated')->default(false)
                ->comment('เป็น action อัตโนมัติ');

            $table->string('triggered_by')->nullable()
                ->comment('ถูก trigger โดย (cron, webhook, manual, alert)');

            // Parent Action
            $table->foreignId('parent_audit_log_id')
                ->nullable()
                ->constrained('ai_rental_audit_logs')
                ->onDelete('set null')
                ->comment('Audit log ที่เป็น parent (chain of actions)');

            // Metadata
            $table->json('metadata')->nullable()
                ->comment('ข้อมูลเพิ่มเติม (JSON)');

            $table->text('notes')->nullable()
                ->comment('บันทึกเพิ่มเติม');

            // Timestamps
            $table->timestamp('action_at')
                ->comment('เวลาที่เกิดการกระทำ');

            $table->timestamps();

            // Indexes - Optimized for querying
            $table->index('user_id');
            $table->index('deployment_id');
            $table->index('cloud_config_id');
            $table->index('action');
            $table->index('action_category');
            $table->index('action_type');
            $table->index('resource_type');
            $table->index('status');
            $table->index('severity');
            $table->index('action_at');
            $table->index(['user_id', 'action_at']);
            $table->index(['deployment_id', 'action_at']);
            $table->index(['action_category', 'action_at']);
            $table->index('ip_address');
            $table->index('is_sensitive');
            $table->index('requires_review');

            // Full-text search index
            $table->fullText(['description', 'error_message'], 'audit_logs_fulltext');
        });
    }

    /**
     * ลบตาราง ai_rental_audit_logs
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_rental_audit_logs');
    }
};
