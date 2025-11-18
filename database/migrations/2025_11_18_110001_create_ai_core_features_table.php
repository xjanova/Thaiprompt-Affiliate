<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง ai_core_features
     *
     * ตารางนี้เก็บ registry ของ AI features ทั้งหมดในระบบ
     * เป็นตารางหลักที่ควบคุมการเข้าถึง AI features
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_core_features')) {
            return;
        }

        Schema::create('ai_core_features', function (Blueprint $table) {
            $table->id();

            // Feature identification
            $table->string('feature_key', 100)->unique()->comment('รหัสเฉพาะของ feature (เช่น ai_bot_marketplace)');
            $table->string('feature_name')->comment('ชื่อ feature ภาษาไทย');
            $table->string('feature_name_en')->nullable()->comment('ชื่อ feature ภาษาอังกฤษ');
            $table->text('description')->nullable()->comment('คำอธิบาย feature');

            // Categorization
            $table->string('category', 50)->index()->comment('หมวดหมู่ (marketplace, generation, line_bot, automation, etc.)');
            $table->string('icon', 100)->nullable()->comment('ชื่อไอคอน (FontAwesome หรือ custom)');
            $table->integer('display_order')->default(0)->comment('ลำดับการแสดงผล');

            // Feature status
            $table->boolean('is_enabled')->default(true)->index()->comment('เปิด/ปิดใช้งานระบบทั้งหมด');
            $table->boolean('is_visible')->default(true)->comment('แสดง/ซ่อนในเมนู');
            $table->boolean('requires_approval')->default(false)->comment('ต้องอนุมัติก่อนใช้งาน');

            // Quota management
            $table->enum('quota_type', ['none', 'count', 'usage', 'duration', 'token'])->default('none')->comment('ประเภทโควต้า');
            $table->integer('default_quota_limit')->nullable()->comment('จำนวนโควต้าเริ่มต้น');
            $table->enum('quota_reset_period', ['daily', 'weekly', 'monthly', 'yearly', 'never'])->nullable()->comment('รอบการรีเซ็ตโควต้า');

            // Pricing tiers
            $table->string('pricing_tier', 50)->nullable()->comment('ระดับราคา (free, basic, pro, enterprise)');
            $table->decimal('base_price', 10, 2)->default(0)->comment('ราคาฐาน (บาท/เดือน)');
            $table->decimal('usage_price', 10, 2)->default(0)->comment('ราคาต่อหน่วยการใช้งาน');

            // Access control
            $table->json('allowed_roles')->nullable()->comment('Roles ที่อนุญาตให้ใช้งาน');
            $table->json('required_permissions')->nullable()->comment('Permissions ที่ต้องมี');

            // Configuration
            $table->json('config')->nullable()->comment('การตั้งค่าเพิ่มเติม (endpoints, API keys, etc.)');
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติม');

            // Provider information
            $table->string('provider_name')->nullable()->comment('ผู้ให้บริการ AI (OpenAI, Google, etc.)');
            $table->string('provider_version')->nullable()->comment('เวอร์ชั่นของผู้ให้บริการ');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['category', 'is_enabled']);
            $table->index(['pricing_tier', 'is_enabled']);
        });
    }

    /**
     * ลบตาราง ai_core_features
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_core_features');
    }
};
