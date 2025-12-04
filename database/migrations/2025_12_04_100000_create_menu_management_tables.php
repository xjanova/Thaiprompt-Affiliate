<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตารางสำหรับระบบจัดการเมนู
 *
 * ระบบนี้ช่วยให้แอดมินสามารถ:
 * - เปิด/ปิดการแสดงเมนูตาม Role
 * - จัดเรียงลำดับเมนู (Drag & Drop)
 * - กำหนดสิทธิ์การเข้าถึงเมนูแต่ละรายการ
 *
 * @version 1.0.0
 * @since 2025-12-04
 */
return new class extends Migration
{
    use SafeMigration;

    /**
     * Run the migrations.
     *
     * สร้างตาราง:
     * - menu_items: เก็บรายการเมนูทั้งหมด
     * - menu_role_settings: เก็บการตั้งค่าสิทธิ์เมนูตาม Role
     */
    public function up(): void
    {
        // ตาราง menu_items - เก็บรายการเมนูทั้งหมด
        if (!Schema::hasTable('menu_items')) {
            Schema::create('menu_items', function (Blueprint $table) {
                $table->id();

                // ข้อมูลหลักของเมนู
                $table->string('menu_key', 100)->unique()->comment('Unique identifier (e.g., admin.dashboard)');
                $table->string('dashboard_type', 50)->comment('ประเภท dashboard: admin, seller, user, provider, instructor');
                $table->unsignedBigInteger('parent_id')->nullable()->comment('Parent menu ID สำหรับ submenu');
                $table->string('label')->comment('ชื่อเมนู (Thai)');
                $table->string('icon', 100)->nullable()->comment('Icon (emoji หรือ Font Awesome class)');

                // Route และ URL
                $table->string('route')->nullable()->comment('Laravel route name');
                $table->string('url')->nullable()->comment('URL fallback ถ้าไม่มี route');

                // การจัดเรียงและแสดงผล
                $table->decimal('order', 8, 2)->default(0)->comment('ลำดับการแสดงผล');
                $table->boolean('is_active')->default(true)->comment('เปิด/ปิดเมนูทั้งระบบ');
                $table->boolean('is_visible')->default(true)->comment('แสดง/ซ่อนเมนู');

                // Permissions และ Conditions
                $table->json('permissions')->nullable()->comment('สิทธิ์ที่ต้องการ (array of permission names)');
                $table->string('condition', 100)->nullable()->comment('เงื่อนไขพิเศษ: hasProviderAccess, hideIfProvider, etc.');
                $table->boolean('hide_if_kyc_verified')->default(false)->comment('ซ่อนเมื่อ KYC verified');

                // Badge
                $table->string('badge', 50)->nullable()->comment('Badge text: NEW, PRO, CORE, HOT');
                $table->string('badge_color')->nullable()->comment('Tailwind badge color class');

                // ข้อมูลเพิ่มเติม
                $table->text('description')->nullable()->comment('คำอธิบายเมนู');
                $table->boolean('is_divider')->default(false)->comment('เป็นเส้นแบ่ง (---) หรือไม่');

                $table->timestamps();

                // Foreign key สำหรับ parent submenu
                $table->foreign('parent_id')
                    ->references('id')
                    ->on('menu_items')
                    ->onDelete('cascade');

                // Indexes
                $table->index(['dashboard_type', 'is_active'], 'menu_items_dash_active_idx');
                $table->index(['parent_id', 'order'], 'menu_items_parent_order_idx');
                $table->index('dashboard_type', 'menu_items_dashboard_idx');
            });
        }

        // ตาราง menu_role_settings - เก็บการตั้งค่าสิทธิ์เมนูตาม Role
        if (!Schema::hasTable('menu_role_settings')) {
            Schema::create('menu_role_settings', function (Blueprint $table) {
                $table->id();

                // Foreign keys
                $table->foreignId('menu_item_id')
                    ->constrained('menu_items')
                    ->onDelete('cascade')
                    ->comment('Menu item ที่ตั้งค่า');

                $table->foreignId('role_id')
                    ->constrained('roles')
                    ->onDelete('cascade')
                    ->comment('Role ที่ตั้งค่า');

                // การตั้งค่า
                $table->boolean('is_visible')->default(true)->comment('แสดง/ซ่อนสำหรับ Role นี้');
                $table->boolean('is_enabled')->default(true)->comment('เปิด/ปิดใช้งานสำหรับ Role นี้');
                $table->decimal('custom_order', 8, 2)->nullable()->comment('ลำดับเฉพาะสำหรับ Role นี้');

                $table->timestamps();

                // Unique constraint
                $table->unique(['menu_item_id', 'role_id'], 'menu_role_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_role_settings');
        Schema::dropIfExists('menu_items');
    }
};
