<?php

/**
 * สร้างตาราง coin_purchases
 *
 * บันทึกประวัติการซื้อสินค้าด้วย Video Coins
 * เก็บข้อมูลการซื้อ สถานะ และรายละเอียดการใช้งาน
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง coin_purchases
     */
    public function up(): void
    {
        if (Schema::hasTable('coin_purchases')) {
            return;
        }

        Schema::create('coin_purchases', function (Blueprint $table) {
            $table->id();

            // ผู้ซื้อ
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // สินค้า
            $table->foreignId('product_id')
                ->constrained('coin_shop_products')
                ->onDelete('cascade');

            // จำนวนที่ซื้อ
            $table->integer('quantity')->default(1);

            // ราคาที่ซื้อ (ราคา ณ เวลาที่ซื้อ)
            $table->decimal('price_coins', 15, 2);           // ราคาต่อชิ้น
            $table->decimal('total_coins', 15, 2);           // ราคารวม
            $table->decimal('discount_coins', 15, 2)->default(0);  // ส่วนลด (ถ้ามี)

            // ข้อมูลสินค้า ณ เวลาที่ซื้อ (snapshot)
            $table->string('product_name');                   // ชื่อสินค้า
            $table->string('product_type');                   // ประเภทสินค้า
            $table->decimal('product_value', 15, 2)->nullable();  // มูลค่าสินค้า
            $table->string('product_value_unit')->nullable(); // หน่วยมูลค่า

            // รหัสอ้างอิง
            $table->string('order_number')->unique();         // เลขที่ใบสั่งซื้อ
            $table->string('redemption_code')->nullable();    // รหัสใช้งาน (สำหรับ voucher/coupon)
            $table->string('tracking_number')->nullable();    // เลขพัสดุ (สำหรับสินค้าจริง)

            // สถานะ
            $table->enum('status', [
                'pending',          // รอดำเนินการ
                'processing',       // กำลังดำเนินการ
                'completed',        // สำเร็จ/ส่งแล้ว
                'used',             // ใช้งานแล้ว
                'expired',          // หมดอายุ
                'cancelled',        // ยกเลิก
                'refunded',         // คืนเงิน
            ])->default('pending');

            $table->text('status_note')->nullable();          // หมายเหตุสถานะ

            // การใช้งาน
            $table->dateTime('used_at')->nullable();          // วันที่ใช้งาน
            $table->text('used_note')->nullable();            // หมายเหตุการใช้งาน
            $table->string('used_location')->nullable();      // สถานที่ใช้งาน
            $table->json('used_data')->nullable();            // ข้อมูลการใช้งานเพิ่มเติม

            // วันหมดอายุ
            $table->dateTime('expires_at')->nullable();       // วันหมดอายุ

            // ข้อมูลจัดส่ง (สำหรับสินค้าจริง)
            $table->json('shipping_address')->nullable();     // ที่อยู่จัดส่ง
            $table->dateTime('shipped_at')->nullable();       // วันที่จัดส่ง
            $table->dateTime('delivered_at')->nullable();     // วันที่ได้รับสินค้า

            // ข้อมูลเพิ่มเติม
            $table->json('meta_data')->nullable();            // ข้อมูลอื่นๆ
            $table->text('admin_note')->nullable();           // หมายเหตุจาก Admin
            $table->text('user_note')->nullable();            // หมายเหตุจากผู้ใช้

            // การคืนเงิน
            $table->dateTime('refunded_at')->nullable();
            $table->decimal('refunded_coins', 15, 2)->nullable();
            $table->text('refund_reason')->nullable();
            $table->unsignedBigInteger('refunded_by')->nullable();

            // Audit
            $table->unsignedBigInteger('processed_by')->nullable();  // Admin ผู้ดำเนินการ

            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('refunded_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('processed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes
            $table->index('user_id');
            $table->index('product_id');
            $table->index('status');
            $table->index('order_number');
            $table->index('redemption_code');
            $table->index('expires_at');
            $table->index('created_at');
            $table->index(['user_id', 'status'], 'cp_user_status_idx');
            $table->index(['status', 'expires_at'], 'cp_status_expires_idx');
        });
    }

    /**
     * ลบตาราง
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_purchases');
    }
};
