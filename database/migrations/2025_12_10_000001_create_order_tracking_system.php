<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างระบบติดตามคำสั่งซื้อและแชท
 *
 * ตาราง:
 * - shipping_providers: รายการบริษัทขนส่ง
 * - order_tracking_history: ประวัติการติดตามสถานะ
 * - order_messages: ระบบแชทระหว่างลูกค้า-ร้านค้า
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตารางบริษัทขนส่ง
        if (! Schema::hasTable('shipping_providers')) {
            Schema::create('shipping_providers', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique()->comment('รหัสขนส่ง เช่น THAIPOST, KERRY');
                $table->string('name')->comment('ชื่อบริษัทขนส่ง');
                $table->string('name_en')->nullable()->comment('ชื่อภาษาอังกฤษ');
                $table->string('logo')->nullable()->comment('โลโก้');
                $table->string('tracking_url')->nullable()->comment('URL ติดตามพัสดุ (ใช้ {tracking_number})');
                $table->string('website')->nullable()->comment('เว็บไซต์');
                $table->string('hotline')->nullable()->comment('เบอร์โทร');
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index('is_active');
                $table->index('sort_order');
            });
        }

        // ตารางประวัติการติดตามสถานะ
        if (! Schema::hasTable('order_tracking_history')) {
            Schema::create('order_tracking_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
                $table->string('status', 50)->comment('สถานะ: pending, paid, processing, shipped, delivered, completed');
                $table->string('title')->comment('หัวข้อ เช่น "กำลังจัดส่ง"');
                $table->text('description')->nullable()->comment('รายละเอียด');
                $table->string('location')->nullable()->comment('ตำแหน่ง/ศูนย์กระจาย');
                $table->string('tracking_number')->nullable()->comment('เลขพัสดุ');
                $table->string('shipping_provider')->nullable()->comment('บริษัทขนส่ง');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('created_by_type', ['system', 'admin', 'seller', 'customer'])->default('system');
                $table->json('meta_data')->nullable()->comment('ข้อมูลเพิ่มเติม');
                $table->timestamp('tracked_at')->useCurrent()->comment('เวลาที่เกิดเหตุการณ์');
                $table->timestamps();

                $table->index(['order_id', 'tracked_at']);
                $table->index('status');
            });
        }

        // ตารางแชทคำสั่งซื้อ
        if (! Schema::hasTable('order_messages')) {
            Schema::create('order_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
                $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
                $table->enum('sender_type', ['customer', 'seller', 'admin'])->comment('ประเภทผู้ส่ง');
                $table->text('message')->comment('ข้อความ');
                $table->string('attachment')->nullable()->comment('ไฟล์แนบ');
                $table->string('attachment_type')->nullable()->comment('ประเภทไฟล์: image, file');
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->foreignId('read_by')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_system_message')->default(false)->comment('ข้อความจากระบบ');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['order_id', 'created_at']);
                $table->index(['sender_id', 'sender_type']);
                $table->index('is_read');
            });
        }

        // เพิ่มคอลัมน์ในตาราง orders ถ้ายังไม่มี
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'shipping_provider_id')) {
                $table->foreignId('shipping_provider_id')->nullable()->after('shipping_fee')
                    ->constrained('shipping_providers')->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'estimated_delivery_at')) {
                $table->timestamp('estimated_delivery_at')->nullable()->after('delivered_at')
                    ->comment('วันที่คาดว่าจะได้รับ');
            }
            if (! Schema::hasColumn('orders', 'has_unread_messages')) {
                $table->boolean('has_unread_messages')->default(false)->after('admin_notes')
                    ->comment('มีข้อความที่ยังไม่ได้อ่าน');
            }
            if (! Schema::hasColumn('orders', 'last_message_at')) {
                $table->timestamp('last_message_at')->nullable()->after('has_unread_messages')
                    ->comment('เวลาข้อความล่าสุด');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ลบคอลัมน์ที่เพิ่มใน orders
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'shipping_provider_id')) {
                $table->dropForeign(['shipping_provider_id']);
                $table->dropColumn('shipping_provider_id');
            }
            if (Schema::hasColumn('orders', 'estimated_delivery_at')) {
                $table->dropColumn('estimated_delivery_at');
            }
            if (Schema::hasColumn('orders', 'has_unread_messages')) {
                $table->dropColumn('has_unread_messages');
            }
            if (Schema::hasColumn('orders', 'last_message_at')) {
                $table->dropColumn('last_message_at');
            }
        });

        Schema::dropIfExists('order_messages');
        Schema::dropIfExists('order_tracking_history');
        Schema::dropIfExists('shipping_providers');
    }
};
