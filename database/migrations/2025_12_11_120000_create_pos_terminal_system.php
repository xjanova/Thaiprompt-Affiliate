<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตารางสำหรับระบบ POS Terminal และ API Keys
 *
 * ระบบ 2 กุญแจ:
 * - Product Key: สร้างที่ POS device อัตโนมัติ (ใช้ hardware info)
 * - API Key: สร้างที่ Server เมื่อ POS ลงทะเบียน (Admin ต้องให้กับลูกค้า)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตาราง pos_api_keys - เก็บ API Keys ที่ Server สร้างให้
        if (! Schema::hasTable('pos_api_keys')) {
            Schema::create('pos_api_keys', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')
                    ->nullable()
                    ->constrained('vendor_stores')
                    ->onDelete('cascade')
                    ->comment('ร้านค้าที่ API Key นี้ผูกอยู่');
                $table->string('key', 100)->unique()->comment('API Key (pk_xxx...)');
                $table->string('name')->nullable()->comment('ชื่อ API Key (สำหรับ Admin ระบุ)');
                $table->text('description')->nullable()->comment('คำอธิบาย');
                $table->boolean('is_active')->default(true)->comment('เปิดใช้งานหรือไม่');
                $table->boolean('is_blocked')->default(false)->comment('ถูกบล็อกโดย Admin หรือไม่');
                $table->string('blocked_reason')->nullable()->comment('เหตุผลที่ถูกบล็อก');
                $table->timestamp('blocked_at')->nullable()->comment('วันที่ถูกบล็อก');
                $table->foreignId('blocked_by')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null')
                    ->comment('Admin ที่บล็อก');
                $table->timestamp('last_used_at')->nullable()->comment('ใช้งานล่าสุด');
                $table->timestamp('expires_at')->nullable()->comment('วันหมดอายุ');
                $table->json('permissions')->nullable()->comment('สิทธิ์การใช้งาน');
                $table->unsignedInteger('rate_limit')->default(1000)->comment('Rate limit ต่อนาที');
                $table->timestamps();
                $table->softDeletes();

                // Indexes
                $table->index('shop_id', 'pos_api_keys_shop_idx');
                $table->index('is_active', 'pos_api_keys_active_idx');
                $table->index('is_blocked', 'pos_api_keys_blocked_idx');
            });
        }

        // ตาราง pos_terminals - เก็บข้อมูล POS ที่ลงทะเบียน
        if (! Schema::hasTable('pos_terminals')) {
            Schema::create('pos_terminals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')
                    ->nullable()
                    ->constrained('vendor_stores')
                    ->onDelete('cascade')
                    ->comment('ร้านค้าที่ POS นี้ผูกอยู่');
                $table->foreignId('api_key_id')
                    ->nullable()
                    ->constrained('pos_api_keys')
                    ->onDelete('set null')
                    ->comment('API Key ที่ใช้งาน');
                $table->string('product_key', 50)->unique()->comment('Product Key จาก POS device');
                $table->string('device_id', 100)->comment('Device ID จาก POS');
                $table->string('device_name')->nullable()->comment('ชื่ออุปกรณ์');
                $table->string('device_model')->nullable()->comment('รุ่นอุปกรณ์');
                $table->string('platform', 50)->nullable()->comment('Platform (WinUI, Android, etc.)');
                $table->string('app_version', 20)->nullable()->comment('เวอร์ชัน App');
                $table->string('terminal_name')->nullable()->comment('ชื่อ Terminal (สำหรับ Admin ตั้ง)');
                $table->enum('status', ['pending', 'active', 'suspended', 'blocked'])
                    ->default('pending')
                    ->comment('สถานะ: pending=รอ API Key, active=ใช้งาน, suspended=ระงับ, blocked=บล็อก');
                $table->boolean('is_verified')->default(false)->comment('ยืนยันด้วย API Key แล้วหรือไม่');
                $table->timestamp('verified_at')->nullable()->comment('วันที่ยืนยัน');
                $table->timestamp('last_sync_at')->nullable()->comment('Sync ล่าสุด');
                $table->string('last_ip_address', 45)->nullable()->comment('IP Address ล่าสุด');
                $table->text('notes')->nullable()->comment('หมายเหตุ');
                $table->json('device_info')->nullable()->comment('ข้อมูลอุปกรณ์เพิ่มเติม');
                $table->timestamps();
                $table->softDeletes();

                // Indexes
                $table->index('shop_id', 'pos_terminals_shop_idx');
                $table->index('status', 'pos_terminals_status_idx');
                $table->index('device_id', 'pos_terminals_device_idx');
            });
        }

        // ตาราง pos_daily_reports - รายงานประจำวัน
        if (! Schema::hasTable('pos_daily_reports')) {
            Schema::create('pos_daily_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('terminal_id')
                    ->constrained('pos_terminals')
                    ->onDelete('cascade')
                    ->comment('POS Terminal');
                $table->date('report_date')->comment('วันที่รายงาน');
                $table->decimal('total_sales', 15, 2)->default(0)->comment('ยอดขายรวม');
                $table->unsignedInteger('total_transactions')->default(0)->comment('จำนวน transactions');
                $table->decimal('total_cash', 15, 2)->default(0)->comment('รับเงินสด');
                $table->decimal('total_card', 15, 2)->default(0)->comment('รับบัตร');
                $table->decimal('total_qr', 15, 2)->default(0)->comment('รับ QR');
                $table->decimal('total_discount', 15, 2)->default(0)->comment('ส่วนลดรวม');
                $table->decimal('total_tax', 15, 2)->default(0)->comment('ภาษีรวม');
                $table->unsignedInteger('items_sold')->default(0)->comment('จำนวนสินค้าที่ขาย');
                $table->json('hourly_sales')->nullable()->comment('ยอดขายรายชั่วโมง');
                $table->json('top_products')->nullable()->comment('สินค้าขายดี');
                $table->timestamp('synced_at')->nullable()->comment('Sync ล่าสุด');
                $table->timestamps();

                // Indexes
                $table->unique(['terminal_id', 'report_date'], 'pos_daily_unique_idx');
                $table->index('report_date', 'pos_daily_date_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_daily_reports');
        Schema::dropIfExists('pos_terminals');
        Schema::dropIfExists('pos_api_keys');
    }
};
