<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง ai_rental_budget_limits
     *
     * เก็บข้อมูล budget limits และ spending controls
     * ช่วยควบคุมค่าใช้จ่ายและแจ้งเตือนเมื่อใกล้เกิน budget
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_rental_budget_limits')) {
            return;
        }

        Schema::create('ai_rental_budget_limits', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ผู้ใช้ที่ตั้ง budget');

            $table->foreignId('cloud_config_id')
                ->nullable()
                ->constrained('ai_rental_cloud_configs')
                ->onDelete('cascade')
                ->comment('Config เฉพาะ (null = ทุก configs)');

            $table->foreignId('deployment_id')
                ->nullable()
                ->constrained('ai_rental_deployments')
                ->onDelete('cascade')
                ->comment('Deployment เฉพาะ (null = ทุก deployments)');

            // Budget Configuration
            $table->string('budget_name')->comment('ชื่อ budget limit');
            $table->text('description')->nullable()
                ->comment('คำอธิบาย');

            $table->enum('budget_type', ['daily', 'weekly', 'monthly', 'total', 'per_deployment'])
                ->default('monthly')
                ->comment('ประเภท budget');

            $table->decimal('limit_amount', 10, 2)
                ->comment('วงเงินจำกัด (USD)');

            $table->decimal('current_spending', 10, 2)->default(0)
                ->comment('ค่าใช้จ่ายปัจจุบัน (USD)');

            // Warning Thresholds
            $table->integer('warning_threshold_1')->default(50)
                ->comment('แจ้งเตือนเมื่อถึง % (default: 50%)');
            $table->integer('warning_threshold_2')->default(75)
                ->comment('แจ้งเตือนเมื่อถึง % (default: 75%)');
            $table->integer('warning_threshold_3')->default(90)
                ->comment('แจ้งเตือนเมื่อถึง % (default: 90%)');

            $table->boolean('threshold_1_triggered')->default(false)
                ->comment('แจ้งเตือนครั้งที่ 1 แล้ว');
            $table->boolean('threshold_2_triggered')->default(false)
                ->comment('แจ้งเตือนครั้งที่ 2 แล้ว');
            $table->boolean('threshold_3_triggered')->default(false)
                ->comment('แจ้งเตือนครั้งที่ 3 แล้ว');

            $table->timestamp('threshold_1_triggered_at')->nullable();
            $table->timestamp('threshold_2_triggered_at')->nullable();
            $table->timestamp('threshold_3_triggered_at')->nullable();

            // Auto Actions
            $table->boolean('auto_stop_at_limit')->default(false)
                ->comment('หยุด deployment อัตโนมัติเมื่อเกิน budget');
            $table->boolean('auto_notify_email')->default(true)
                ->comment('ส่ง email แจ้งเตือนอัตโนมัติ');
            $table->boolean('auto_notify_push')->default(true)
                ->comment('ส่ง push notification อัตโนมัติ');

            // Status
            $table->enum('status', ['active', 'paused', 'exceeded', 'expired'])
                ->default('active')
                ->comment('สถานะ budget limit');

            $table->boolean('is_active')->default(true)
                ->comment('เปิดใช้งาน');

            // Period Tracking
            $table->timestamp('period_start')->nullable()
                ->comment('วันเริ่มต้น period');
            $table->timestamp('period_end')->nullable()
                ->comment('วันสิ้นสุด period');
            $table->timestamp('last_reset_at')->nullable()
                ->comment('รีเซ็ตล่าสุดเมื่อ');

            // Statistics
            $table->integer('total_alerts_sent')->default(0)
                ->comment('จำนวน alerts ที่ส่งไปแล้ว');
            $table->integer('times_exceeded')->default(0)
                ->comment('จำนวนครั้งที่เกิน budget');
            $table->timestamp('last_exceeded_at')->nullable()
                ->comment('เกิน budget ล่าสุดเมื่อ');

            // Rollover
            $table->boolean('allow_rollover')->default(false)
                ->comment('อนุญาตให้ budget เหลือย้ายไปงวดถัดไป');
            $table->decimal('rollover_amount', 10, 2)->default(0)
                ->comment('จำนวนเงินที่ย้ายมา (USD)');

            // Notifications
            $table->json('notification_emails')->nullable()
                ->comment('Email addresses เพิ่มเติมสำหรับแจ้งเตือน');
            $table->json('webhook_urls')->nullable()
                ->comment('Webhook URLs สำหรับแจ้งเตือน');

            // Metadata
            $table->json('metadata')->nullable()
                ->comment('ข้อมูลเพิ่มเติม (JSON)');
            $table->text('notes')->nullable()
                ->comment('บันทึก');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('cloud_config_id');
            $table->index('deployment_id');
            $table->index('budget_type');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index('period_start');
            $table->index('period_end');
        });
    }

    /**
     * ลบตาราง ai_rental_budget_limits
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_rental_budget_limits');
    }
};
