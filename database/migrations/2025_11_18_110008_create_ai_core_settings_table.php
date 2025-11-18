<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง ai_core_settings
     *
     * ตารางนี้เก็บการตั้งค่าต่างๆ ของระบบ AI Core
     * รองรับการตั้งค่าแบบ global และ tenant-specific
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_core_settings')) {
            return;
        }

        Schema::create('ai_core_settings', function (Blueprint $table) {
            $table->id();

            // Scope (global หรือ tenant-specific)
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('ai_core_tenants')
                ->onDelete('cascade')
                ->comment('Tenant เฉพาะ (null = global setting)');

            $table->foreignId('feature_id')
                ->nullable()
                ->constrained('ai_core_features')
                ->onDelete('cascade')
                ->comment('Feature เฉพาะ (null = general setting)');

            // Setting information
            $table->string('setting_key')->comment('รหัสการตั้งค่า (เช่น max_concurrent_requests)');
            $table->string('setting_group', 100)->nullable()->index()->comment('กลุ่มการตั้งค่า (performance, security, ui, etc.)');
            $table->string('setting_name')->comment('ชื่อการตั้งค่า');
            $table->text('description')->nullable()->comment('คำอธิบาย');

            // Value storage (polymorphic)
            $table->enum('value_type', ['string', 'integer', 'float', 'boolean', 'json', 'array'])->comment('ประเภทข้อมูล');
            $table->text('value')->nullable()->comment('ค่าที่ตั้ง');
            $table->text('default_value')->nullable()->comment('ค่า default');

            // Validation
            $table->text('validation_rules')->nullable()->comment('กฎการตรวจสอบ (JSON)');
            $table->json('allowed_values')->nullable()->comment('ค่าที่อนุญาต (สำหรับ enum)');
            $table->string('min_value')->nullable()->comment('ค่าต่ำสุด');
            $table->string('max_value')->nullable()->comment('ค่าสูงสุด');

            // UI presentation
            $table->enum('input_type', ['text', 'number', 'select', 'checkbox', 'radio', 'textarea', 'json', 'toggle'])->default('text')->comment('ประเภท input สำหรับ UI');
            $table->string('display_order')->default(0)->comment('ลำดับการแสดง');
            $table->boolean('is_visible')->default(true)->comment('แสดงใน UI');
            $table->boolean('is_editable')->default(true)->comment('สามารถแก้ไขได้');

            // Access control
            $table->boolean('is_sensitive')->default(false)->comment('ข้อมูลละเอียดอ่อน (ซ่อนค่า)');
            $table->json('required_permissions')->nullable()->comment('Permissions ที่ต้องมีเพื่อแก้ไข');

            // Override hierarchy (global < tenant < feature)
            $table->boolean('can_be_overridden')->default(true)->comment('สามารถ override ได้ที่ level ถัดไป');
            $table->foreignId('overridden_from')->nullable()->comment('Setting ต้นฉบับที่ override มา');

            // Environment specific
            $table->string('environment')->nullable()->comment('Environment เฉพาะ (production, staging, etc.)');

            // Audit trail
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete()->comment('ผู้แก้ไขล่าสุด');
            $table->text('change_reason')->nullable()->comment('เหตุผลในการเปลี่ยนแปลง');

            // Metadata
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติม');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['tenant_id', 'feature_id', 'setting_key'], 'setting_scope_key_unique');
            $table->index(['setting_group', 'is_visible']);
            $table->index('setting_key');
        });
    }

    /**
     * ลบตาราง ai_core_settings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_core_settings');
    }
};
