<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตารางสำหรับระบบ Mobile App Management
 *
 * Admin ควบคุมได้ 3 อย่าง:
 * 1. mobile_devices - เก็บข้อมูลเครื่องสำหรับ Analytics
 * 2. mobile_banners - จัดการ Banner โฆษณา
 * 3. mobile_push_notifications - จัดการ Push Notifications
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =====================================================
        // 1. mobile_devices - ข้อมูลเครื่องที่ลงทะเบียน
        // =====================================================
        if (! Schema::hasTable('mobile_devices')) {
            Schema::create('mobile_devices', function (Blueprint $table) {
                $table->id();
                $table->string('device_id', 100)->unique()->comment('Unique device identifier');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('platform', ['ios', 'android'])->index();
                $table->string('device_model', 100)->nullable()->comment('เช่น iPhone 15 Pro');
                $table->string('device_brand', 100)->nullable()->comment('เช่น Apple, Samsung');
                $table->string('os_version', 50)->nullable()->comment('เวอร์ชัน OS');
                $table->string('app_version', 20)->comment('เวอร์ชันแอพ');
                $table->string('push_token', 500)->nullable()->comment('FCM/APNS token');
                $table->string('locale', 10)->default('th-TH');
                $table->string('timezone', 50)->default('Asia/Bangkok');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_active_at')->nullable()->index();
                $table->timestamps();

                // Indexes
                $table->index(['platform', 'app_version']);
                $table->index(['created_at']);
            });
        }

        // =====================================================
        // 2. mobile_banners - Banner โฆษณาในแอพ
        // =====================================================
        if (! Schema::hasTable('mobile_banners')) {
            Schema::create('mobile_banners', function (Blueprint $table) {
                $table->id();
                $table->string('title', 100)->comment('หัวข้อ banner');
                $table->string('image', 500)->comment('URL รูปภาพ');
                $table->string('link', 500)->nullable()->comment('ลิงก์เมื่อคลิก');
                $table->enum('link_type', ['internal', 'external', 'product', 'category'])
                    ->default('internal');
                $table->string('link_target', 100)->nullable()->comment('เป้าหมาย (product_id, category_id)');
                $table->enum('position', ['top', 'middle', 'bottom'])->default('top')->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('start_date')->nullable()->comment('วันเริ่มแสดง');
                $table->timestamp('end_date')->nullable()->comment('วันสิ้นสุด');
                $table->unsignedBigInteger('view_count')->default(0)->comment('จำนวน views');
                $table->unsignedBigInteger('click_count')->default(0)->comment('จำนวน clicks');
                $table->timestamps();

                // Index
                $table->index(['is_active', 'position', 'sort_order']);
            });
        }

        // =====================================================
        // 3. mobile_push_notifications - Push Notifications
        // =====================================================
        if (! Schema::hasTable('mobile_push_notifications')) {
            Schema::create('mobile_push_notifications', function (Blueprint $table) {
                $table->id();
                $table->string('title', 100)->comment('หัวข้อ notification');
                $table->text('body')->comment('ข้อความ');
                $table->string('image', 500)->nullable()->comment('URL รูปภาพ');
                $table->json('data')->nullable()->comment('ข้อมูลเพิ่มเติม');
                $table->enum('target_platform', ['all', 'ios', 'android'])->default('all');
                $table->enum('status', ['pending', 'scheduled', 'sent', 'failed'])
                    ->default('pending')
                    ->index();
                $table->timestamp('scheduled_at')->nullable()->comment('เวลาตั้งเวลาส่ง');
                $table->timestamp('sent_at')->nullable()->comment('เวลาส่งจริง');
                $table->unsignedInteger('success_count')->default(0);
                $table->unsignedInteger('failure_count')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                // Index
                $table->index(['status', 'scheduled_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_push_notifications');
        Schema::dropIfExists('mobile_banners');
        Schema::dropIfExists('mobile_devices');
    }
};
