<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง ai_rental_alerts
     *
     * เก็บข้อมูล alerts, notifications และ warnings สำหรับระบบ AI Rental
     * รองรับ real-time monitoring และแจ้งเตือนผู้ใช้
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_rental_alerts')) {
            return;
        }

        Schema::create('ai_rental_alerts', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ผู้ใช้ที่ได้รับ alert');

            $table->foreignId('deployment_id')
                ->nullable()
                ->constrained('ai_rental_deployments')
                ->onDelete('cascade')
                ->comment('Deployment ที่เกี่ยวข้อง (ถ้ามี)');

            // ข้อมูล Alert
            $table->string('alert_type')->comment('ประเภท alert');
            // alert_type: cost_warning, cost_limit_exceeded, deployment_failed,
            //             deployment_stopped, health_check_failed, budget_exceeded,
            //             high_error_rate, slow_response, resource_low, etc.

            $table->enum('severity', ['info', 'warning', 'error', 'critical'])
                ->default('info')
                ->comment('ระดับความสำคัญ');

            $table->string('title')->comment('หัวข้อ alert');
            $table->text('message')->comment('ข้อความแจ้งเตือน');
            $table->text('description')->nullable()
                ->comment('รายละเอียดเพิ่มเติม');

            // เงื่อนไขที่ trigger alert
            $table->json('trigger_conditions')->nullable()
                ->comment('เงื่อนไขที่ทำให้เกิด alert (JSON)');

            $table->json('alert_data')->nullable()
                ->comment('ข้อมูลเพิ่มเติม (metrics, values, etc.)');

            // Actions
            $table->string('action_required')->nullable()
                ->comment('การกระทำที่แนะนำ');
            $table->text('action_url')->nullable()
                ->comment('URL สำหรับดำเนินการ');
            $table->string('action_label')->nullable()
                ->comment('ป้ายชื่อปุ่ม action');

            // Status
            $table->enum('status', ['pending', 'sent', 'read', 'acknowledged', 'resolved'])
                ->default('pending')
                ->comment('สถานะ alert');

            $table->timestamp('sent_at')->nullable()
                ->comment('เวลาที่ส่ง notification');
            $table->timestamp('read_at')->nullable()
                ->comment('เวลาที่อ่าน');
            $table->timestamp('acknowledged_at')->nullable()
                ->comment('เวลาที่ acknowledge');
            $table->timestamp('resolved_at')->nullable()
                ->comment('เวลาที่แก้ไขเสร็จ');

            // Notification Channels
            $table->json('notification_channels')->nullable()
                ->comment('ช่องทางแจ้งเตือน (email, sms, push, webhook)');
            $table->boolean('email_sent')->default(false)
                ->comment('ส่ง email แล้ว');
            $table->boolean('push_sent')->default(false)
                ->comment('ส่ง push notification แล้ว');
            $table->boolean('webhook_sent')->default(false)
                ->comment('ส่ง webhook แล้ว');

            // Auto-resolution
            $table->boolean('auto_resolved')->default(false)
                ->comment('แก้ไขอัตโนมัติแล้ว');
            $table->text('resolution_note')->nullable()
                ->comment('หมายเหตุการแก้ไข');

            // Metrics (ณ เวลาที่เกิด alert)
            $table->decimal('cost_at_alert', 10, 2)->nullable()
                ->comment('ค่าใช้จ่าย ณ เวลาที่เกิด alert');
            $table->decimal('budget_limit', 10, 2)->nullable()
                ->comment('Budget limit ที่ตั้งไว้');
            $table->integer('error_count')->nullable()
                ->comment('จำนวน errors');
            $table->decimal('response_time_ms', 10, 2)->nullable()
                ->comment('Response time เฉลี่ย');

            // Priority & Expiry
            $table->integer('priority')->default(5)
                ->comment('ลำดับความสำคัญ (1=สูงสุด, 10=ต่ำสุด)');
            $table->timestamp('expires_at')->nullable()
                ->comment('Alert หมดอายุเมื่อ');

            // Metadata
            $table->json('metadata')->nullable()
                ->comment('ข้อมูลเพิ่มเติม (JSON)');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('deployment_id');
            $table->index('alert_type');
            $table->index('severity');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index('sent_at');
            $table->index('created_at');
        });
    }

    /**
     * ลบตาราง ai_rental_alerts
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_rental_alerts');
    }
};
