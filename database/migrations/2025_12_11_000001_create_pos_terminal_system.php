<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างระบบ POS Terminal Management
 *
 * ตาราง:
 * - pos_api_keys: API Keys สำหรับร้านค้า (สร้างจาก Admin Panel)
 * - pos_terminals: เครื่อง POS ที่ลงทะเบียน
 * - pos_daily_reports: รายงานยอดขายรายวัน
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตาราง API Keys สำหรับ POS
        if (! Schema::hasTable('pos_api_keys')) {
            Schema::create('pos_api_keys', function (Blueprint $table) {
                $table->id();
                // ⚠️ ใช้ unsignedBigInteger แทน constrained() เพราะตาราง shops อาจยังไม่มี
                $table->unsignedBigInteger('shop_id')->comment('Shop ID');
                $table->string('name')->comment('ชื่อ API Key เช่น "สาขาหลัก"');
                $table->string('key', 64)->unique()->comment('API Key (เก็บ hashed)');
                $table->string('key_prefix', 8)->comment('ตัวอักษรต้น key สำหรับแสดง');
                $table->text('description')->nullable()->comment('รายละเอียด');
                $table->integer('max_terminals')->default(1)->comment('จำนวนเครื่องที่อนุญาต (0=ไม่จำกัด)');
                $table->boolean('is_active')->default(true);
                $table->timestamp('expires_at')->nullable()->comment('วันหมดอายุ');
                $table->timestamp('last_used_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['shop_id', 'is_active']);
                $table->index('key_prefix');
            });
        }

        // ตาราง POS Terminals
        if (! Schema::hasTable('pos_terminals')) {
            Schema::create('pos_terminals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('api_key_id')->constrained('pos_api_keys')->onDelete('cascade');
                // ⚠️ ใช้ unsignedBigInteger แทน constrained() เพราะตาราง shops อาจยังไม่มี
                $table->unsignedBigInteger('shop_id')->comment('Shop ID');
                $table->string('product_key', 50)->unique()->comment('Product Key ที่ generate จากเครื่อง');
                $table->string('device_id', 100)->comment('Device ID (hardware fingerprint)');
                $table->string('device_name')->default('POS Terminal')->comment('ชื่อเครื่อง');
                $table->string('device_model')->nullable()->comment('รุ่นเครื่อง');
                $table->string('platform', 50)->nullable()->comment('Platform: windows, android, ios');
                $table->string('app_version', 20)->nullable()->comment('เวอร์ชันแอป');
                $table->boolean('is_active')->default(true);
                $table->timestamp('registered_at')->useCurrent()->comment('วันที่ลงทะเบียน');
                $table->timestamp('last_seen_at')->nullable()->comment('เข้าใช้งานล่าสุด');
                $table->timestamp('last_sync_at')->nullable()->comment('Sync ข้อมูลล่าสุด');
                $table->json('settings')->nullable()->comment('ตั้งค่าเครื่อง');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['api_key_id', 'is_active']);
                $table->index(['shop_id', 'is_active']);
                $table->index('device_id');
                $table->unique(['api_key_id', 'device_id'], 'pos_terminals_api_device_unique');
            });
        }

        // ตารางรายงานยอดขายรายวัน
        if (! Schema::hasTable('pos_daily_reports')) {
            Schema::create('pos_daily_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pos_terminal_id')->constrained('pos_terminals')->onDelete('cascade');
                // ⚠️ ใช้ unsignedBigInteger แทน constrained() เพราะตาราง shops อาจยังไม่มี
                $table->unsignedBigInteger('shop_id')->comment('Shop ID');
                $table->date('report_date')->comment('วันที่รายงาน');
                $table->decimal('total_sales', 12, 2)->default(0)->comment('ยอดขายรวม');
                $table->integer('total_orders')->default(0)->comment('จำนวนออเดอร์');
                $table->integer('total_items')->default(0)->comment('จำนวนรายการสินค้า');
                $table->decimal('cash_sales', 12, 2)->default(0)->comment('ยอดขายเงินสด');
                $table->decimal('card_sales', 12, 2)->default(0)->comment('ยอดขายบัตร');
                $table->decimal('other_sales', 12, 2)->default(0)->comment('ยอดขายอื่นๆ');
                $table->decimal('discount_total', 12, 2)->default(0)->comment('ส่วนลดรวม');
                $table->decimal('tax_total', 12, 2)->default(0)->comment('ภาษีรวม');
                $table->json('hourly_sales')->nullable()->comment('ยอดขายรายชั่วโมง');
                $table->json('top_products')->nullable()->comment('สินค้าขายดี');
                $table->timestamp('synced_at')->nullable()->comment('เวลาที่ sync ข้อมูล');
                $table->timestamps();

                $table->unique(['pos_terminal_id', 'report_date'], 'pos_daily_reports_terminal_date');
                $table->index(['shop_id', 'report_date']);
                $table->index('report_date');
            });
        }

        // เพิ่มคอลัมน์ในตาราง orders สำหรับ POS
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'pos_terminal_id')) {
                    // ⚠️ ไม่ใช้ after('shop_id') เพราะคอลัมน์ shop_id อาจไม่มี
                    $table->unsignedBigInteger('pos_terminal_id')->nullable()
                        ->comment('เครื่อง POS ที่สร้าง order');
                }
                if (! Schema::hasColumn('orders', 'pos_local_id')) {
                    $table->string('pos_local_id', 50)->nullable()
                        ->comment('Local ID จากเครื่อง POS');
                }
                if (! Schema::hasColumn('orders', 'pos_synced_at')) {
                    $table->timestamp('pos_synced_at')->nullable()
                        ->comment('เวลาที่ sync จาก POS');
                }
            });
        }

        // เพิ่ม index สำหรับ POS orders
        Schema::table('orders', function (Blueprint $table) {
            // ตรวจสอบว่ามี index อยู่แล้วหรือไม่
            $indexNames = collect(Schema::getIndexes('orders'))->pluck('name')->toArray();

            if (! in_array('orders_pos_terminal_local_idx', $indexNames)) {
                $table->index(['pos_terminal_id', 'pos_local_id'], 'orders_pos_terminal_local_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ลบ index และคอลัมน์ POS ในตาราง orders
        Schema::table('orders', function (Blueprint $table) {
            // ลบ index ก่อน
            $indexNames = collect(Schema::getIndexes('orders'))->pluck('name')->toArray();

            if (in_array('orders_pos_terminal_local_idx', $indexNames)) {
                $table->dropIndex('orders_pos_terminal_local_idx');
            }

            if (Schema::hasColumn('orders', 'pos_terminal_id')) {
                $table->dropForeign(['pos_terminal_id']);
                $table->dropColumn('pos_terminal_id');
            }
            if (Schema::hasColumn('orders', 'pos_local_id')) {
                $table->dropColumn('pos_local_id');
            }
            if (Schema::hasColumn('orders', 'pos_synced_at')) {
                $table->dropColumn('pos_synced_at');
            }
        });

        Schema::dropIfExists('pos_daily_reports');
        Schema::dropIfExists('pos_terminals');
        Schema::dropIfExists('pos_api_keys');
    }
};
