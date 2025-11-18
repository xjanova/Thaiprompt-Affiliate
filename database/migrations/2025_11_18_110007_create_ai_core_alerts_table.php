<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง ai_core_alerts
     *
     * ตารางนี้เก็บการแจ้งเตือนและ alerts ต่างๆ
     * เช่น quota ใกล้หมด, feature error, scheduled action, etc.
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_core_alerts')) {
            return;
        }

        Schema::create('ai_core_alerts', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('ai_core_tenants')
                ->onDelete('cascade')
                ->comment('Tenant ที่เกี่ยวข้อง');

            $table->foreignId('feature_id')
                ->nullable()
                ->constrained('ai_core_features')
                ->onDelete('cascade')
                ->comment('Feature ที่เกี่ยวข้อง');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('ผู้ใช้ที่เกี่ยวข้อง');

            // Alert information
            $table->enum('alert_type', [
                'quota_warning',
                'quota_exceeded',
                'feature_error',
                'feature_disabled',
                'schedule_executed',
                'schedule_failed',
                'subscription_expiring',
                'subscription_expired',
                'payment_failed',
                'system_maintenance',
                'custom'
            ])->index()->comment('ประเภท alert');

            $table->enum('severity', ['info', 'warning', 'error', 'critical'])->default('info')->index()->comment('ระดับความสำคัญ');

            $table->string('title')->comment('หัวข้อ alert');
            $table->text('message')->comment('ข้อความ alert');

            // Action and resolution
            $table->string('action_url')->nullable()->comment('URL สำหรับดำเนินการ');
            $table->string('action_label')->nullable()->comment('ข้อความปุ่มดำเนินการ');
            $table->boolean('requires_action')->default(false)->comment('ต้องดำเนินการหรือไม่');

            // Status
            $table->enum('status', ['unread', 'read', 'acknowledged', 'resolved', 'dismissed'])->default('unread')->index()->comment('สถานะ alert');
            $table->timestamp('read_at')->nullable()->comment('วันเวลาที่อ่าน');
            $table->timestamp('acknowledged_at')->nullable()->comment('วันเวลาที่รับทราบ');
            $table->timestamp('resolved_at')->nullable()->comment('วันเวลาที่แก้ไขแล้ว');

            // Notification tracking
            $table->json('notification_channels')->nullable()->comment('ช่องทางที่ส่ง (email, line, sms)');
            $table->boolean('notification_sent')->default(false)->comment('ส่งการแจ้งเตือนแล้ว');
            $table->timestamp('notification_sent_at')->nullable()->comment('วันเวลาที่ส่งการแจ้งเตือน');

            // Grouping and deduplication
            $table->string('alert_key')->nullable()->index()->comment('รหัสสำหรับจัดกลุ่ม alert ซ้ำ');
            $table->integer('occurrence_count')->default(1)->comment('จำนวนครั้งที่เกิด alert นี้');
            $table->timestamp('first_occurred_at')->nullable()->comment('ครั้งแรกที่เกิด');
            $table->timestamp('last_occurred_at')->nullable()->comment('ครั้งล่าสุดที่เกิด');

            // Expiration
            $table->timestamp('expires_at')->nullable()->index()->comment('วันเวลาที่ alert หมดอายุ');
            $table->boolean('auto_dismiss')->default(false)->comment('ปิด alert อัตโนมัติเมื่อหมดอายุ');

            // Related data
            $table->json('related_data')->nullable()->comment('ข้อมูลเพิ่มเติมที่เกี่ยวข้อง');
            $table->json('metadata')->nullable()->comment('Metadata');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'status', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index(['alert_type', 'severity']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * ลบตาราง ai_core_alerts
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_core_alerts');
    }
};
