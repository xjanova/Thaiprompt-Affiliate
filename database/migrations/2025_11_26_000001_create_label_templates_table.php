<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration สำหรับสร้างตาราง label_templates
 *
 * เก็บข้อมูล template สำหรับออกฉลาก บาร์โค้ด QR Code
 * สำหรับทำสติ๊กเกอร์ติดผลิตภัณฑ์
 *
 * @author Claude AI
 */
return new class extends Migration
{
    /**
     * สร้างตาราง label_templates
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('label_templates')) {
            return;
        }

        Schema::create('label_templates', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์กับ user (nullable สำหรับ system templates)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade');

            // ข้อมูลพื้นฐาน
            $table->string('name'); // ชื่อ template
            $table->text('description')->nullable(); // คำอธิบาย
            $table->string('category')->nullable(); // หมวดหมู่ (product, price, shipping, etc.)

            // ขนาดกระดาษ/ฉลาก (หน่วย: mm)
            $table->decimal('paper_width', 8, 2)->default(50); // ความกว้าง
            $table->decimal('paper_height', 8, 2)->default(30); // ความสูง
            $table->string('paper_unit', 10)->default('mm'); // หน่วย (mm, cm, in)
            $table->string('paper_type', 50)->nullable(); // ประเภทกระดาษ (sticker, label, etc.)

            // การจัดวาง (สำหรับพิมพ์หลายชิ้นต่อแผ่น)
            $table->integer('columns')->default(1); // จำนวนคอลัมน์
            $table->integer('rows')->default(1); // จำนวนแถว
            $table->decimal('gap_horizontal', 8, 2)->default(0); // ระยะห่างแนวนอน
            $table->decimal('gap_vertical', 8, 2)->default(0); // ระยะห่างแนวตั้ง
            $table->decimal('margin_top', 8, 2)->default(0); // ขอบบน
            $table->decimal('margin_left', 8, 2)->default(0); // ขอบซ้าย
            $table->decimal('margin_right', 8, 2)->default(0); // ขอบขวา
            $table->decimal('margin_bottom', 8, 2)->default(0); // ขอบล่าง

            // เนื้อหาและ elements (JSON)
            $table->json('elements')->nullable(); // องค์ประกอบทั้งหมดบนฉลาก

            // การตั้งค่าเพิ่มเติม
            $table->json('settings')->nullable(); // ตั้งค่าทั่วไป
            $table->json('print_settings')->nullable(); // ตั้งค่าการพิมพ์

            // สถานะและการแชร์
            $table->boolean('is_public')->default(false); // แชร์เป็น public template
            $table->boolean('is_system')->default(false); // เป็น system template
            $table->boolean('is_active')->default(true); // เปิด/ปิดใช้งาน
            $table->integer('usage_count')->default(0); // จำนวนครั้งที่ใช้งาน

            // ภาพตัวอย่าง
            $table->string('thumbnail_path')->nullable(); // path รูป thumbnail

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id', 'label_tpl_user_idx');
            $table->index('category', 'label_tpl_category_idx');
            $table->index('is_public', 'label_tpl_public_idx');
            $table->index('is_system', 'label_tpl_system_idx');
            $table->index('is_active', 'label_tpl_active_idx');
            $table->index('created_at', 'label_tpl_created_idx');
        });
    }

    /**
     * ลบตาราง label_templates
     */
    public function down(): void
    {
        Schema::dropIfExists('label_templates');
    }
};
