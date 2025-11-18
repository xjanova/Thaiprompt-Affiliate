<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง ai_core_feature_access
     *
     * ตารางนี้เก็บสิทธิ์การเข้าถึง AI features ของแต่ละ tenant
     * ควบคุมว่า tenant ไหนสามารถใช้ feature ใดได้บ้าง
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_core_feature_access')) {
            return;
        }

        Schema::create('ai_core_feature_access', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('tenant_id')
                ->constrained('ai_core_tenants')
                ->onDelete('cascade')
                ->comment('Tenant ที่มีสิทธิ์');

            $table->foreignId('feature_id')
                ->constrained('ai_core_features')
                ->onDelete('cascade')
                ->comment('Feature ที่อนุญาต');

            // Access control
            $table->boolean('is_enabled')->default(true)->index()->comment('เปิด/ปิดการใช้งาน feature นี้');
            $table->enum('access_level', ['none', 'read', 'write', 'full'])->default('full')->comment('ระดับการเข้าถึง');

            // Quota overrides (override จาก feature default)
            $table->integer('quota_limit')->nullable()->comment('จำนวนโควต้าเฉพาะ tenant นี้');
            $table->enum('quota_reset_period', ['daily', 'weekly', 'monthly', 'yearly', 'never'])->nullable()->comment('รอบรีเซ็ตโควต้า');
            $table->integer('quota_used')->default(0)->comment('จำนวนโควต้าที่ใช้ไปแล้ว');
            $table->timestamp('quota_reset_at')->nullable()->comment('วันที่จะรีเซ็ตโควต้าครั้งถัดไป');

            // Trial and expiration
            $table->boolean('is_trial')->default(false)->comment('กำลังทดลองใช้');
            $table->timestamp('trial_ends_at')->nullable()->comment('วันที่ trial หมดอายุ');
            $table->timestamp('access_starts_at')->nullable()->comment('วันที่เริ่มมีสิทธิ์');
            $table->timestamp('access_ends_at')->nullable()->comment('วันที่หมดสิทธิ์');

            // Approval workflow
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('approved')->index()->comment('สถานะการอนุมัติ');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->comment('ผู้อนุมัติ');
            $table->timestamp('approved_at')->nullable()->comment('วันที่อนุมัติ');
            $table->text('rejection_reason')->nullable()->comment('เหตุผลที่ปฏิเสธ');

            // Custom settings
            $table->json('custom_config')->nullable()->comment('การตั้งค่าเฉพาะ tenant นี้');
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติม');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['tenant_id', 'feature_id'], 'tenant_feature_unique');
            $table->index(['tenant_id', 'is_enabled']);
            $table->index(['feature_id', 'status']);
            $table->index('access_ends_at');
        });
    }

    /**
     * ลบตาราง ai_core_feature_access
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_core_feature_access');
    }
};
