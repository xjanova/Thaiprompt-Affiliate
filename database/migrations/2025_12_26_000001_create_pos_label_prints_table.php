<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration สำหรับสร้างตาราง pos_label_prints
 *
 * เก็บประวัติการพิมพ์ฉลากสำหรับ POS System
 * รองรับทั้ง Product Labels และ Shipping Labels
 *
 * @author Claude AI
 */
return new class extends Migration
{
    /**
     * สร้างตาราง pos_label_prints
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('pos_label_prints')) {
            return;
        }

        Schema::create('pos_label_prints', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('pos_transaction_id')
                ->nullable()
                ->constrained('pos_transactions')
                ->onDelete('set null');

            $table->foreignId('template_id')
                ->nullable()
                ->constrained('label_templates')
                ->onDelete('set null');

            // ประเภทการพิมพ์
            $table->enum('print_type', [
                'product_label',    // ฉลากสินค้า
                'shipping_label',   // ใบปะสินค้า
                'price_tag',        // ฉลากราคา
                'barcode_only',     // barcode อย่างเดียว
                'custom',            // กำหนดเอง
            ])->default('product_label');

            // ข้อมูลสินค้าที่พิมพ์ (JSON Array)
            // [
            //   {
            //     "product_id": 1,
            //     "product_name": "สินค้า A",
            //     "barcode": "123456789",
            //     "price": 100,
            //     "quantity": 5,
            //     "sku": "SKU-001"
            //   }
            // ]
            $table->json('products');

            // จำนวนฉลาก
            $table->integer('total_labels')->default(0); // รวมกี่ดวง
            $table->integer('sheets_count')->default(0); // ใช้กี่แผ่น

            // ข้อมูลกระดาษ
            $table->string('paper_size')->nullable(); // A4, thermal_80mm, etc.
            $table->decimal('paper_width', 8, 2)->nullable(); // mm
            $table->decimal('paper_height', 8, 2)->nullable(); // mm

            // การตั้งค่าการพิมพ์ (JSON)
            // {
            //   "orientation": "portrait|landscape",
            //   "scale": 100,
            //   "copies": 1,
            //   "color_mode": "color|grayscale",
            //   "quality": "draft|normal|high",
            //   "printer_name": "HP LaserJet"
            // }
            $table->json('print_settings')->nullable();

            // ข้อมูลเครื่องพิมพ์
            $table->string('printer_name')->nullable(); // ชื่อเครื่องพิมพ์
            $table->string('printer_type')->nullable(); // thermal, inkjet, laser
            $table->foreignId('pos_device_id')
                ->nullable()
                ->constrained('pos_devices')
                ->onDelete('set null');

            // สถานะการพิมพ์
            $table->enum('status', [
                'pending',      // รอพิมพ์
                'printing',     // กำลังพิมพ์
                'completed',    // พิมพ์สำเร็จ
                'failed',       // พิมพ์ล้มเหลว
                'cancelled',     // ยกเลิก
            ])->default('pending');

            $table->timestamp('printed_at')->nullable(); // วันเวลาที่พิมพ์
            $table->text('error_message')->nullable(); // ข้อความ error (ถ้ามี)

            // Metadata
            $table->json('metadata')->nullable(); // ข้อมูลเพิ่มเติม
            $table->string('print_session_id')->nullable(); // session id สำหรับ batch print

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id', 'pos_lbl_prints_user_idx');
            $table->index('pos_transaction_id', 'pos_lbl_prints_trans_idx');
            $table->index('template_id', 'pos_lbl_prints_tpl_idx');
            $table->index('print_type', 'pos_lbl_prints_type_idx');
            $table->index('status', 'pos_lbl_prints_status_idx');
            $table->index('printed_at', 'pos_lbl_prints_printed_idx');
            $table->index('print_session_id', 'pos_lbl_prints_session_idx');
            $table->index('created_at', 'pos_lbl_prints_created_idx');
        });
    }

    /**
     * ลบตาราง pos_label_prints
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_label_prints');
    }
};
