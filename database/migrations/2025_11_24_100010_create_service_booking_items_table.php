<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง service_booking_items - รายการในการจอง
     *
     * เก็บรายละเอียดแต่ละรายการในการจอง (บริการ, สินค้า, ออฟชั่น)
     * ใช้สำหรับแสดงใบเสร็จและคำนวณราคา
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('service_booking_items')) {
            return;
        }

        Schema::create('service_booking_items', function (Blueprint $table) {
            $table->id();

            // การจองที่เป็นของ
            $table->foreignId('booking_id')
                ->constrained('service_bookings')
                ->onDelete('cascade')
                ->comment('การจอง');

            // ประเภทรายการ
            $table->enum('item_type', ['service', 'product', 'option', 'distance_fee'])
                ->comment('ประเภท: บริการ, สินค้า, ออฟชั่น, ค่าเดินทาง');

            $table->unsignedBigInteger('item_id')->nullable()->comment('ID ของ service/product (null สำหรับ option/distance_fee)');

            // รายละเอียดรายการ
            $table->string('name', 255)->comment('ชื่อรายการ');
            $table->text('description')->nullable()->comment('คำอธิบาย');

            // จำนวนและราคา
            $table->integer('quantity')->default(1)->comment('จำนวน');
            $table->decimal('unit_price', 10, 2)->default(0)->comment('ราคาต่อหน่วย');
            $table->decimal('subtotal', 10, 2)->default(0)->comment('รวม (quantity × unit_price)');

            // Metadata
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติม');

            $table->timestamps();

            // Indexes
            $table->index('booking_id', 'service_booking_item_booking_idx');
            $table->index(['item_type', 'item_id'], 'service_booking_item_type_idx');
        });
    }

    /**
     * ลบตาราง service_booking_items
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_booking_items');
    }
};
