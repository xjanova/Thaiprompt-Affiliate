<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง ai_core_tenants
     *
     * ตารางนี้เก็บข้อมูล tenants สำหรับ multi-tenancy
     * รองรับการให้เช่า AI features แบบ SaaS
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_core_tenants')) {
            return;
        }

        Schema::create('ai_core_tenants', function (Blueprint $table) {
            $table->id();

            // Tenant identification
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('เจ้าของ tenant (ถ้าเป็น user-based)');

            $table->string('tenant_key', 100)->unique()->comment('รหัสเฉพาะของ tenant');
            $table->string('tenant_name')->comment('ชื่อ tenant');
            $table->string('tenant_type', 50)->default('user')->comment('ประเภท tenant (user, organization, reseller)');

            // Status
            $table->enum('status', ['active', 'suspended', 'trial', 'expired'])->default('active')->index()->comment('สถานะ tenant');
            $table->boolean('is_enabled')->default(true)->index()->comment('เปิด/ปิดการใช้งาน');

            // Subscription information
            $table->string('subscription_tier', 50)->nullable()->comment('แพ็กเกจที่สมัคร (free, basic, pro, enterprise)');
            $table->timestamp('subscription_starts_at')->nullable()->comment('วันที่เริ่มสมัคร');
            $table->timestamp('subscription_ends_at')->nullable()->comment('วันที่หมดอายุ');
            $table->boolean('auto_renew')->default(true)->comment('ต่ออายุอัตโนมัติ');

            // Limits and quotas
            $table->json('global_quotas')->nullable()->comment('โควต้ารวมทั้งหมด');
            $table->json('feature_limits')->nullable()->comment('ข้อจำกัดเฉพาะ feature');

            // Contact and billing
            $table->string('contact_email')->nullable()->comment('อีเมลติดต่อ');
            $table->string('contact_phone', 20)->nullable()->comment('เบอร์โทรติดต่อ');
            $table->text('billing_address')->nullable()->comment('ที่อยู่สำหรับเรียกเก็บเงิน');
            $table->string('tax_id', 50)->nullable()->comment('เลขประจำตัวผู้เสียภาษี');

            // Settings
            $table->json('settings')->nullable()->comment('การตั้งค่าเฉพาะ tenant');
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติม');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'is_enabled']);
            $table->index('subscription_tier');
            $table->index('subscription_ends_at');
        });
    }

    /**
     * ลบตาราง ai_core_tenants
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_core_tenants');
    }
};
