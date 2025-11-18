<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง ai_core_schedules
     *
     * ตารางนี้เก็บตารางเวลาการเปิด/ปิด AI features
     * รองรับการตั้งเวลาอัตโนมัติและ recurring schedules
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_core_schedules')) {
            return;
        }

        Schema::create('ai_core_schedules', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('ai_core_tenants')
                ->onDelete('cascade')
                ->comment('Tenant เฉพาะ (null = ทุก tenant)');

            $table->foreignId('feature_id')
                ->constrained('ai_core_features')
                ->onDelete('cascade')
                ->comment('Feature ที่จะตั้งเวลา');

            // Schedule information
            $table->string('schedule_name')->comment('ชื่อ schedule');
            $table->text('description')->nullable()->comment('คำอธิบาย');

            // Action
            $table->enum('action', ['enable', 'disable', 'toggle'])->comment('การกระทำ (เปิด/ปิด/สลับ)');

            // Time settings
            $table->enum('schedule_type', ['once', 'recurring', 'cron'])->default('once')->comment('ประเภท schedule');
            $table->timestamp('starts_at')->nullable()->comment('วันเวลาเริ่มต้น (สำหรับ once)');
            $table->timestamp('ends_at')->nullable()->comment('วันเวลาสิ้นสุด');

            // Recurring schedule
            $table->enum('recurrence', ['daily', 'weekly', 'monthly', 'yearly'])->nullable()->comment('รูปแบบการทำซ้ำ');
            $table->json('recurrence_days')->nullable()->comment('วันที่ทำซ้ำ (เช่น [1,3,5] สำหรับ Mon,Wed,Fri)');
            $table->time('recurrence_time')->nullable()->comment('เวลาที่ทำซ้ำทุกวัน');

            // Cron expression (advanced)
            $table->string('cron_expression')->nullable()->comment('Cron expression (เช่น 0 9 * * 1-5)');

            // Timezone
            $table->string('timezone', 50)->default('Asia/Bangkok')->comment('Timezone สำหรับ schedule');

            // Status
            $table->boolean('is_enabled')->default(true)->index()->comment('เปิด/ปิด schedule นี้');
            $table->enum('status', ['pending', 'active', 'completed', 'failed', 'cancelled'])->default('pending')->index()->comment('สถานะ schedule');

            // Execution tracking
            $table->timestamp('last_executed_at')->nullable()->comment('ครั้งล่าสุดที่ทำงาน');
            $table->timestamp('next_execution_at')->nullable()->index()->comment('ครั้งถัดไปที่จะทำงาน');
            $table->integer('execution_count')->default(0)->comment('จำนวนครั้งที่ทำงานแล้ว');
            $table->integer('max_executions')->nullable()->comment('จำนวนครั้งสูงสุด (null = ไม่จำกัด)');

            // Error handling
            $table->integer('retry_count')->default(0)->comment('จำนวนครั้งที่ retry');
            $table->integer('max_retries')->default(3)->comment('จำนวน retry สูงสุด');
            $table->text('last_error')->nullable()->comment('Error ล่าสุด');

            // Notification settings
            $table->boolean('notify_on_execution')->default(false)->comment('แจ้งเตือนเมื่อทำงาน');
            $table->boolean('notify_on_failure')->default(true)->comment('แจ้งเตือนเมื่อล้มเหลว');
            $table->json('notification_channels')->nullable()->comment('ช่องทางแจ้งเตือน (email, line, etc.)');

            // Metadata
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติม');

            // Created by
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('ผู้สร้าง schedule');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['feature_id', 'is_enabled', 'status']);
            $table->index(['tenant_id', 'is_enabled']);
            $table->index(['next_execution_at', 'is_enabled']);
        });
    }

    /**
     * ลบตาราง ai_core_schedules
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_core_schedules');
    }
};
